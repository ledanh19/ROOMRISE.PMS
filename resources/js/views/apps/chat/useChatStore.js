import {
  buildUrl,
  fetchJSON,
  initialsOf,
  mergeContactPreserveMessages,
  parseJSONSafe,
  sortChats,
  uniqById,
} from "@/utils/helper";
import { usePage } from "@inertiajs/vue3";
import { defineStore } from "pinia";
import { computed } from "vue";

// ---------- Basics ----------
const page = usePage();
const user = computed(() => page.props.auth.user);
const adminName = computed(() => user.value?.name || "Guest");

const TYPE_MESAGE = { TEXT: "TEXT", IMAGE: "IMAGE" };

const nowISO = () => new Date().toISOString();
const uid = (() => {
  let i = 1000;
  return () => ++i;
})();

// ---------- Mappers ----------
const mapHistoryItem = (it, meId) => {
  const historyId = it?.id;
  const unread = Number(it?.isNew ?? 0);
  const time = it?.updatedAt || nowISO();
  const fullName = it?.customerName || "Guest";
  return {
    id: historyId,
    fullName,
    propertyName: it?.propertyName ?? "",
    messageThreadId: it?.messageThreadId,
    externalBookingId: it?.externalBookingId,
    bookingId: it?.bookingId,
    otaName: it?.otaName ?? null,
    avatar: null,
    avatarText: initialsOf(fullName),
    nightsCount: Number(it?.nightsCount || 0),
    roomsCount: Number(it?.roomsCount || 0),
    role: it?.propertyName || "",
    state: (it?.messageStatus || it?.state) === "ACTIVE" ? "active" : "closed",
    checkInDate: it?.checkInDate,
    tag: it?.tag,
    chat: {
      id: historyId,
      unseenMsgs: unread,
      lastMessage: {
        id: historyId,
        type: it?.type || TYPE_MESAGE.TEXT,
        message: it?.lastContent || "",
        time,
        isSeen: unread === 0,
        feedback: { isSeen: unread === 0, isDelivered: true },
      },
      messages: [],
    },
  };
};

const mapDetailItem = (it, meId, contactId) => {
  const isAdmin =
    it?.isAdminSend === 1 ||
    it?.is_admin_send === 1 ||
    it?.sender_type === "ADMIN";
  const profilePhotoPath = it?.profilePhotoPath ?? "";
  const senderId = isAdmin ? meId : contactId;
  const time = it?.createdAt || nowISO();
  const text = it?.content ?? "";
  const type = it?.type || TYPE_MESAGE.TEXT;
  const isSeen = !!it?.userId;
  const isDelivered = "is_delivered" in (it || {}) ? !!it.is_delivered : true;
  return {
    id: it?.id,
    message: text,
    type,
    senderId,
    time,
    isSeen,
    feedback: { isSeen, isDelivered },
    username: it?.username ?? null,
    profilePhotoPath,
    isAdmin,
  };
};

const ensureEntry = (store, contactId, messageHistoryId) => {
  let entry = store.chatsContacts.find((c) => c.id === contactId);
  if (!entry) {
    entry = {
      id: contactId,
      fullName: "Guest",
      avatar: null,
      avatarText: initialsOf("Guest"),
      chat: { id: messageHistoryId, messages: [], unseenMsgs: 0 },
    };
    store.chatsContacts.push(entry);
  }
  entry.chat ||= { id: messageHistoryId, messages: [], unseenMsgs: 0 };
  return entry;
};

const syncContactsFromChats = (store) => {
  store.contacts = store.chatsContacts.map((c) => ({
    id: c.id,
    fullName: c.fullName,
    avatar: null,
    avatarText: c.avatarText || initialsOf(c.fullName),
  }));
};

const newLocalMsg = ({
  id = uid(),
  type = TYPE_MESAGE.TEXT,
  message = "",
  senderId,
  time = nowISO(),
  isSeen = true,
  isDelivered = false,
  username = null,
  profilePhotoPath = "",
  isAdmin = true,
} = {}) => ({
  id,
  type,
  message,
  senderId,
  time,
  isSeen,
  feedback: { isSeen, isDelivered },
  username,
  profilePhotoPath,
});

// ---------- Helpers (store-internal) ----------
const normalizeState = (status) =>
  String(status || "ACTIVE").toUpperCase() === "CLOSED" ? "closed" : "active";

// ---------- Store ----------
export const useChatStore = defineStore("chat", {
  state: () => ({
    contacts: [],
    chatsContacts: [],
    profileUser: {
      id: 1,
      fullName: "You",
      avatar: null,
      profilePhotoPath: null,
    },
    activeChat: null,
    filterStatus: "ACTIVE",
    isErrorSendMessage: null,
    _errMsgTimer: null,

    _listSig: "",
    _page: 1,
    _size: 10,
    _hasMore: true,

    _lastQuery: "",
    _lastProperty: null,
    _lastStatus: "ACTIVE",
    _swBound: false,
    _pushRefreshTimer: null,
  }),

  actions: {
    // --------- misc ---------
    _clearSendError() {
      if (this._errMsgTimer) {
        clearTimeout(this._errMsgTimer);
        this._errMsgTimer = null;
      }
      this.isErrorSendMessage = null;
    },
    _devInjectPush(p) {
      return this.handleIncomingPush(p);
    },

    attachServiceWorkerListenerOnce() {
      if (this._swBound) return;
      this._swBound = true;
      if ("serviceWorker" in navigator) {
        navigator.serviceWorker.addEventListener("message", async (e) => {
          const data = e?.data || {};
          if (data.source === "fcm-sw") {
            try {
              await this.handleIncomingPush(data.info || data.payload || data);
            } catch (err) {
              console.error("handleIncomingPush (from SW) error:", err);
            }
          }
        });
      }
    },

    scheduleRefreshListAfterPush(delay = 150) {
      try {
        if (this._pushRefreshTimer) clearTimeout(this._pushRefreshTimer);
      } catch {}
      this._pushRefreshTimer = setTimeout(async () => {
        try {
          await this.fetchChatsAndContacts(
            this._lastQuery ?? "",
            1,
            this._size ?? 10,
            this._lastProperty ?? null,
            this._lastStatus ?? this.filterStatus,
            false
          );
        } catch (e) {
          console.warn("[chat] auto refresh after push error:", e);
        }
      }, delay);
    },

    // --------- Push handler ---------
    async handleIncomingPush(payload) {
      if (import.meta?.env?.DEV)
        console.log("[FCM] incoming payload:", payload);

      const d = (payload && payload.data) || payload || {};
      const mh = parseJSONSafe(d.messageHistory); // stringified JSON
      const md = parseJSONSafe(d.message); // stringified JSON

      // Pre-calc conversation id from the richest source
      let contactId =
        mh?.id ||
        d.messageHistoryId ||
        d.historyId ||
        d.messageThreadId ||
        d.threadId ||
        d.externalBookingId ||
        null;

      // ===== 1) Upsert list row via messageHistory =====
      if (mh && mh.id) {
        const meId = this.profileUser?.id ?? 1;

        const mapped = mapHistoryItem(
          {
            id: mh.id,
            lastContent: mh.lastContent,
            tag: mh.tag,
            type: mh.type || mh.messageType || "TEXT",
            isNew: mh.isNew,
            customerName: mh.customerName || mh.fullName,
            propertyName: mh.propertyName,
            otaName: mh.otaName,
            externalBookingId: mh.externalBookingId,
            bookingId: mh.bookingId,
            messageThreadId: mh.messageThreadId,
            messageStatus: mh.messageStatus || mh.status, // ACTIVE | CLOSED
            nightsCount: mh.nightsCount ?? mh.nights_count,
            roomsCount: mh.roomsCount ?? mh.rooms_count,
            checkInDate: mh.checkInDate ?? mh.check_in_date,
            checkOutDate: mh.checkOutDate ?? mh.check_out_date,
            updatedAt: mh.updatedAt,
          },
          meId
        );

        const forcedState = normalizeState(mh.messageStatus || mh.status);
        mapped.state = forcedState;

        const activeId = this.activeChat?.contact?.id;
        const isActive = String(activeId) === String(mh.id);

        // If active → mark as read locally & server
        if (isActive) {
          mapped.chat.unseenMsgs = 0;
          if (mapped.chat.lastMessage) mapped.chat.lastMessage.isSeen = true;
          try {
            await this.readMessage(mh.id);
          } catch (e) {
            /* silent */
          }
        }

        const oldIdx = this.chatsContacts.findIndex(
          (c) => String(c.id) === String(mh.id)
        );
        if (oldIdx === -1) {
          this.chatsContacts.unshift(mapped);
        } else {
          const old = this.chatsContacts[oldIdx];
          const merged = mergeContactPreserveMessages(old, mapped);
          merged.state = forcedState;

          if (isActive) {
            if (old.chat?.messages?.length) {
              merged.chat = merged.chat || {};
              merged.chat.messages = old.chat.messages;
              // lastMessage theo list (mapped) để update preview
              merged.chat.lastMessage =
                mapped.chat?.lastMessage || merged.chat.lastMessage || null;
            }
            this.activeChat = {
              contact: merged,
              chat: merged.chat || this.activeChat.chat,
            };
          }

          this.chatsContacts.splice(oldIdx, 1);
          this.chatsContacts.unshift(merged);
        }

        // Force re-render for some virtual lists
        this.chatsContacts = this.chatsContacts.slice();
        syncContactsFromChats(this);
      }

      // ===== 2) If active & have message detail → append directly to messages =====
      if (
        md &&
        contactId &&
        String(this.activeChat?.contact?.id) === String(contactId)
      ) {
        const meId = this.profileUser?.id ?? 1;
        const entry = ensureEntry(this, contactId, contactId);

        entry.state = entry.state || "active";
        entry.chat.messages = entry.chat.messages || [];
        const dup = entry.chat.messages.some(
          (m) => String(m.id) === String(md.id)
        );
        if (!dup) {
          const item = mapDetailItem(
            {
              id: md.id,
              isAdminSend: md.isAdminSend,
              content: md.content,
              type: md.type || "TEXT",
              createdAt: md.createdAt,
              userId: meId, // để mapDetailItem set isSeen=true; vẫn override ngay dưới
              username: md.username ?? null,
              profilePhotoPath: md.profilePhotoPath ?? "",
            },
            meId,
            contactId
          );

          // Because it's the active chat → mark seen
          item.isSeen = true;
          item.feedback = {
            ...(item.feedback || {}),
            isSeen: true,
            isDelivered: true,
          };

          entry.chat.messages.push(item);
          entry.chat.lastMessage = item;
          entry.chat.unseenMsgs = 0;

          this.activeChat.chat = entry.chat;
        }
      }

      // ===== 3) Debounced refetch for server alignment =====
      this.scheduleRefreshListAfterPush(150);
    },

    // --------- UI helpers ---------
    resetActive() {
      this.activeChat = null;
    },

    setProfileUser(payload = {}) {
      const { id, fullName, role, about, status, profilePhotoPath, avatar } =
        payload || {};
      this.profileUser = {
        ...this.profileUser,
        ...(id !== undefined ? { id } : {}),
        ...(fullName !== undefined ? { fullName } : {}),
        ...(role !== undefined ? { role } : {}),
        ...(about !== undefined ? { about } : {}),
        ...(status !== undefined ? { status } : {}),
        ...(profilePhotoPath !== undefined ? { profilePhotoPath } : {}),
      };
      const resolvedAvatar = profilePhotoPath
        ? profilePhotoPath
        : avatar || this.profileUser.avatar || null;
      this.profileUser.avatar = resolvedAvatar;
    },

    setSendError(msg, timeout = 3500) {
      this.isErrorSendMessage = String(
        msg || "Lỗi không gửi được tin nhắn channex."
      );
      if (this._errMsgTimer) clearTimeout(this._errMsgTimer);
      this._errMsgTimer = setTimeout(() => {
        this.isErrorSendMessage = null;
        this._errMsgTimer = null;
      }, timeout);
    },

    // --------- API calls ---------
    async fetchChatsAndContacts(
      q = "",
      page = 1,
      size = 10,
      propertyId = null,
      messageStatusArg,
      append = false
    ) {
      try {
        const messageStatus = messageStatusArg ?? this.filterStatus ?? "ACTIVE";
        const json = await fetchJSON(
          buildUrl("/api/admin/message-history/list-message-histories", {
            q,
            page,
            size,
            property: propertyId,
            propertyId: propertyId,
            messageStatus,
            status: messageStatus,
          })
        );

        const arr = json?.data?.result ?? (Array.isArray(json) ? json : []);
        const me = this.profileUser?.id ?? 1;
        const pageItems = arr.map((raw) => mapHistoryItem(raw, me));
        const unique = uniqById(pageItems);
        const listSig = `${q}|${messageStatus}|${propertyId}`;

        if (append && this._listSig === listSig) {
          const merged = this.chatsContacts.slice();
          const idxMap = new Map(merged.map((x, i) => [x.id, i]));
          for (const item of unique) {
            const idx = idxMap.get(item.id);
            if (idx === undefined) {
              merged.push(item);
              idxMap.set(item.id, merged.length - 1);
            } else {
              const old = merged[idx];
              merged[idx] = mergeContactPreserveMessages(old, item);
            }
          }
          this.chatsContacts = merged;
        } else {
          const oldMap = new Map(this.chatsContacts.map((x) => [x.id, x]));
          const merged = unique.map((item) =>
            mergeContactPreserveMessages(oldMap.get(item.id), item)
          );
          this.chatsContacts = sortChats(merged);
          this._listSig = listSig;
        }

        this._lastQuery = q;
        this._lastProperty = propertyId;
        this._lastStatus = messageStatus;
        syncContactsFromChats(this);
        this._page = page;
        this._size = size;
        this._hasMore = unique.length >= size;

        const currActiveId = this.activeChat?.contact?.id;
        if (currActiveId) {
          const fresh = this.chatsContacts.find((x) => x.id === currActiveId);
          if (fresh) {
            const preservedMsgs = this.activeChat?.chat?.messages;
            if (preservedMsgs?.length) {
              fresh.chat = fresh.chat || {};
              fresh.chat.messages = preservedMsgs;
            }
            this.activeChat = {
              ...this.activeChat,
              contact: fresh,
              chat: fresh.chat || this.activeChat.chat,
            };
          } else {
            this.activeChat = null;
          }
        }

        return {
          items: unique,
          hasMore: this._hasMore,
          page: this._page,
          size: this._size,
        };
      } catch (err) {
        console.error("fetchChatsAndContacts error:", err);
        throw err;
      }
    },

    async getChat(userId) {
      const entry = this.chatsContacts.find((c) => c.id === userId);
      if (entry?.chat?.id) {
        const historyId = entry.chat.id;
        await this.fetchMessageDetails(historyId, userId, {
          page: 1,
          size: 10,
          mode: "replace",
        });
        this.activeChat = { contact: entry, chat: entry.chat };

        const shouldMarkRead =
          (entry.chat?.unseenMsgs ?? 0) > 0 ||
          (entry.chat?.lastMessage && entry.chat.lastMessage.isSeen === false);
        if (shouldMarkRead) {
          try {
            await this.readMessage(historyId);
            entry.chat.unseenMsgs = 0;
            if (entry.chat.lastMessage) entry.chat.lastMessage.isSeen = true;
            entry.chat.messages = (entry.chat.messages || []).map((m) => ({
              ...m,
              isSeen: true,
              feedback: { ...(m.feedback || {}), isSeen: true },
            }));
            if (this.activeChat?.contact?.id === userId)
              this.activeChat.chat = entry.chat;
          } catch (e) {
            console.error("read-message error:", e);
          }
        }
      } else {
        const contact = this.contacts.find((c) => c.id === userId) || {
          id: userId,
          fullName: "Guest",
          avatar: null,
        };
        this.activeChat = {
          contact,
          chat: { id: userId, messages: [], unseenMsgs: 0, userId },
        };
      }
    },

    async fetchMessageDetails(messageHistoryId, contactId, options = {}) {
      const { page = 1, size = 20, mode = "replace" } = options || {};
      try {
        const url = buildUrl("/api/admin/message-detail/list-message-details", {
          id: messageHistoryId,
          page,
          size,
        });
        const json = await fetchJSON(url);
        const arr = json?.data?.result ?? (Array.isArray(json) ? json : []);
        const me = this.profileUser?.id ?? 1;
        const pageMessages = arr
          .map((it) => mapDetailItem(it, me, contactId))
          .sort((a, b) => new Date(a.time) - new Date(b.time));
        const entry = ensureEntry(this, contactId, messageHistoryId);
        if (mode === "prepend") {
          const merged = uniqById([
            ...pageMessages,
            ...(entry.chat.messages || []),
          ]).sort((a, b) => new Date(a.time) - new Date(b.time));
          entry.chat.messages = merged;
        } else {
          entry.chat.messages = pageMessages;
          entry.chat.unseenMsgs = 0;
        }
        entry.chat.lastMessage =
          (entry.chat.messages &&
            entry.chat.messages[entry.chat.messages.length - 1]) ||
          entry.chat.lastMessage ||
          null;
        if (this.activeChat?.contact?.id === contactId)
          this.activeChat.chat = entry.chat;
        const hasMore = pageMessages.length >= size;
        return { items: pageMessages, hasMore, page, size };
      } catch (err) {
        console.error("fetchMessageDetails error:", err);
        throw err;
      }
    },

    async sendMsg(input) {
      if (!this.activeChat) return;
      const meId = this.profileUser?.id ?? 1;
      const contact = this.activeChat?.contact || {};
      const historyId = this.activeChat?.chat?.id ?? contact?.id;
      const messageThreadId =
        contact?.messageThreadId ||
        this.activeChat?.contact?.messageThreadId ||
        null;
      const externalBookingId =
        contact?.externalBookingId ||
        this.activeChat?.contact?.externalBookingId ||
        null;

      const fileToDataUrl = (file) =>
        new Promise((resolve, reject) => {
          const r = new FileReader();
          r.onload = () => resolve(String(r.result));
          r.onerror = reject;
          r.readAsDataURL(file);
        });
      const dataUrlToBase64 = (dataUrl) => {
        if (typeof dataUrl !== "string") return "";
        const i = dataUrl.indexOf(",");
        return i >= 0 ? dataUrl.slice(i + 1) : dataUrl;
      };

      const isImagePayload =
        typeof input === "object" && input?.type === "IMAGE";
      let messageType = TYPE_MESAGE.TEXT;
      let content = "";
      let base64Str = "";
      let fileName, fileType;
      let dataUrl = "";
      if (isImagePayload) {
        messageType = TYPE_MESAGE.IMAGE;
        if (input.file instanceof File) {
          dataUrl = await fileToDataUrl(input.file);
          base64Str = dataUrlToBase64(dataUrl);
          fileName = input.file.name || "photo.jpeg";
          fileType = input.file.type || "image/jpeg";
        } else if (typeof input.file === "string") {
          base64Str = input.file;
          fileName = input.fileName || "photo.jpeg";
          fileType = input.fileType || "image/jpeg";
        } else {
          this.setSendError?.("Thiếu tệp ảnh để gửi.");
          return;
        }
      } else {
        messageType = TYPE_MESAGE.TEXT;
        content = String(input ?? "").trim();
        if (!content) return;
      }

      const contactId = this.activeChat?.contact?.id;
      let entry =
        contactId != null
          ? this.chatsContacts.find((c) => c.id === contactId)
          : null;
      if (!entry) {
        entry = {
          id: contactId,
          fullName: this.activeChat?.contact?.fullName || "Guest",
          avatar: null,
          avatarText: initialsOf(this.activeChat?.contact?.fullName || "Guest"),
          chat: { id: historyId, messages: [], unseenMsgs: 0 },
        };
        this.chatsContacts.push(entry);
      }

      const tmpId = `tmp_${uid()}`;
      const localMsg = newLocalMsg({
        id: tmpId,
        type: messageType,
        message: messageType === TYPE_MESAGE.TEXT ? content : dataUrl,
        senderId: meId,
        time: nowISO(),
        isSeen: true,
        isDelivered: false,
        username: adminName?.value ?? "You",
        isAdmin: true,
      });
      if (isImagePayload && input.caption) localMsg.caption = input.caption;

      entry.chat.messages = entry.chat.messages || [];
      entry.chat.messages.push(localMsg);
      entry.chat.lastMessage = localMsg;
      if (this.activeChat?.contact?.id === contactId) {
        this.activeChat.chat = entry.chat;
      }

      const bodyCommon = {
        externalBookingId,
        messageHistoryId: historyId,
        messageThreadId,
      };
      const body =
        messageType === TYPE_MESAGE.TEXT
          ? { ...bodyCommon, messageType: "TEXT", content }
          : {
              ...bodyCommon,
              messageType: "IMAGE",
              fileName,
              fileType,
              file: base64Str,
              ...(input.caption ? { caption: input.caption } : {}),
            };

      try {
        const json = await fetchJSON(
          buildUrl("/api/admin/message-history/send-message", {}),
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body),
          }
        );
        const ok =
          json &&
          (json.statusCode === 200 || json.status === 200) &&
          (json.data === true || json.success === true);
        if (!ok) {
          this.setSendError?.(
            "Gửi không thành công: phản hồi máy chủ không hợp lệ."
          );
          throw new Error("Send message failed (unexpected response)");
        }

        const idx = entry.chat.messages.findIndex((m) => m.id === tmpId);
        if (idx !== -1) {
          entry.chat.messages[idx] = {
            ...entry.chat.messages[idx],
            feedback: {
              ...(entry.chat.messages[idx].feedback || {}),
              isDelivered: true,
            },
          };
          entry.chat.lastMessage = entry.chat.messages[idx];
        }

        await this.fetchChatsAndContacts(
          this._lastQuery ?? "",
          this._page,
          this._size,
          this._lastProperty ?? null,
          this._lastStatus ?? this.filterStatus,
          true
        );
        const cid = this.activeChat?.contact?.id;
        if (cid != null) {
          const i = this.chatsContacts.findIndex((c) => c.id === cid);
          if (i > 0) {
            const [it] = this.chatsContacts.splice(i, 1);
            this.chatsContacts.unshift(it);
          }
        }
      } catch (err) {
        console.error("sendMsg error:", err);
        this.setSendError?.(`Không gửi được tin nhắn. ${err?.message || ""}`);
        const idx = entry.chat.messages.findIndex((m) => m.id === tmpId);
        if (idx !== -1) {
          entry.chat.messages.splice(idx, 1);
          entry.chat.lastMessage =
            entry.chat.messages[entry.chat.messages.length - 1] || null;
        }
        throw err;
      }
    },

    async closeConversationByHistoryId(messageThreadId) {
      try {
        if (!messageThreadId)
          throw new Error("Không tìm thấy messageThreadId để đóng hội thoại.");
        await fetchJSON(
          buildUrl("/api/admin/message-history/close-message", {}),
          {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ messageThreadId }),
          }
        );

        const isActiveTab = this.filterStatus === "ACTIVE";
        const isClosingActiveChat =
          this.activeChat?.contact?.messageThreadId === messageThreadId;
        const idx = this.chatsContacts.findIndex(
          (c) => c.messageThreadId === messageThreadId
        );
        if (idx !== -1) {
          if (isActiveTab) {
            const removed = this.chatsContacts.splice(idx, 1)[0];
            const cIdx = this.contacts.findIndex((c) => c.id === removed.id);
            if (cIdx !== -1) this.contacts.splice(cIdx, 1);
            if (isClosingActiveChat) this.activeChat = null;
          } else {
            this.chatsContacts[idx] = {
              ...this.chatsContacts[idx],
              state: "closed",
            };
            if (isClosingActiveChat && this.activeChat)
              this.activeChat.contact.state = "closed";
          }
        }
        return true;
      } catch (err) {
        console.error("closeConversationByHistoryId error:", err);
        throw err;
      }
    },

    async readMessage(historyId) {
      await fetchJSON(buildUrl("/api/admin/message-history/read-message", {}), {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ messageHistoryId: historyId }),
      });
    },
  },
});

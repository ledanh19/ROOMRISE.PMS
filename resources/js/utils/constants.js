export const COOKIE_MAX_AGE_1_YEAR = 365 * 24 * 60 * 60;

export const INVENTORY_MAX_DATE = 499;

export const PAGINATION_OPTIONS = [
  { value: 5, title: "5" },
  { value: 25, title: "25" },
  { value: 50, title: "50" },
  { value: 100, title: "100" },
  { value: -1, title: "All" },
];

export const MEAL_TYPE_OPTIONS = [
  { value: "none", label: "Không bao gồm" },
  { value: "all_inclusive", label: "Trọn gói" },
  { value: "breakfast", label: "Bữa sáng" },
  { value: "lunch", label: "Bữa trưa" },
  { value: "dinner", label: "Bữa tối" },
  { value: "american", label: "Kiểu Mỹ" },
  { value: "bed_and_breakfast", label: "Phòng và bữa sáng" },
  { value: "buffet_breakfast", label: "Buffet sáng" },
  { value: "carribean_breakfast", label: "Bữa sáng kiểu Caribe" },
  { value: "continental_breakfast", label: "Bữa sáng kiểu Âu" },
  { value: "english_breakfast", label: "Bữa sáng kiểu Anh" },
  { value: "european_plan", label: "Gói kiểu Châu Âu" },
  { value: "family_plan", label: "Gói gia đình" },
  { value: "full_board", label: "Ăn đủ 3 bữa" },
  { value: "full_breakfast", label: "Bữa sáng đầy đủ" },
  { value: "half_board", label: "Ăn sáng và tối" },
  { value: "room_only", label: "Chỉ phòng" },
  { value: "self_catering", label: "Tự phục vụ" },
  { value: "bermuda", label: "Kiểu Bermuda" },
  {
    value: "dinner_bed_and_breakfast_plan",
    label: "Bữa tối, giường và bữa sáng",
  },
  { value: "family_american", label: "Gói gia đình kiểu Mỹ" },
  { value: "breakfast_and_lunch", label: "Bữa sáng và trưa" },
  { value: "lunch_and_dinner", label: "Bữa trưa và tối" },
];

const MEAL_TYPE_MAP = MEAL_TYPE_OPTIONS.reduce((map, option) => {
  map[option.value] = option.label;
  return map;
}, {});

export const getMealTypeLabel = (value) => {
  return MEAL_TYPE_MAP[value] || value; // Trả về value nếu không tìm thấy
};

export const CURRENCIES = [
  { text: "Việt Nam Đồng (₫)", value: "VND", locale: "vi-VN" },
  { text: "US Dollar ($)", value: "USD", locale: "en-US" },
  { text: "Euro (€)", value: "EUR", locale: "de-DE" },
  { text: "British Pound (£)", value: "GBP", locale: "en-GB" },
  { text: "Japanese Yen (¥)", value: "JPY", locale: "ja-JP" },
  { text: "South Korean Won (₩)", value: "KRW", locale: "ko-KR" },
  { text: "Chinese Yuan (¥)", value: "CNY", locale: "zh-CN" },
  { text: "Singapore Dollar (S$)", value: "SGD", locale: "en-SG" },
  { text: "Thai Baht (฿)", value: "THB", locale: "th-TH" },
  { text: "Australian Dollar (A$)", value: "AUD", locale: "en-AU" },
  { text: "Canadian Dollar (C$)", value: "CAD", locale: "en-CA" },
  { text: "Swiss Franc (CHF)", value: "CHF", locale: "de-CH" },
  { text: "Hong Kong Dollar (HK$)", value: "HKD", locale: "en-HK" },
  { text: "Malaysian Ringgit (RM)", value: "MYR", locale: "ms-MY" },
  { text: "Indian Rupee (₹)", value: "INR", locale: "en-IN" },
  { text: "Philippine Peso (₱)", value: "PHP", locale: "en-PH" },
  { text: "Indonesian Rupiah (Rp)", value: "IDR", locale: "id-ID" },
  { text: "Saudi Riyal (﷼)", value: "SAR", locale: "ar-SA" },
  { text: "UAE Dirham (د.إ)", value: "AED", locale: "ar-AE" },
  { text: "Russian Ruble (₽)", value: "RUB", locale: "ru-RU" },
];

export const ROOM_STATUS = ["Sẵn sàng", "Phòng bẩn", "Đang dọn", "Đóng"];

export const PROPERTY_TYPE_OPTIONS = [
  { value: "apart_hotel", label: "Căn hộ dịch vụ" },
  { value: "apartment", label: "Căn hộ" },
  { value: "boat", label: "Thuyền" },
  { value: "camping", label: "Cắm trại" },
  { value: "capsule_hotel", label: "Khách sạn con nhộng" },
  { value: "chalet", label: "Nhà gỗ trên núi" },
  { value: "country_house", label: "Nhà ở vùng quê" },
  { value: "farm_stay", label: "Nông trại lưu trú" },
  { value: "guest_house", label: "Nhà khách" },
  { value: "holiday_home", label: "Nhà nghỉ dưỡng" },
  { value: "holiday_park", label: "Công viên nghỉ dưỡng" },
  { value: "homestay", label: "Homestay" },
  { value: "hostel", label: "Nhà trọ" },
  { value: "hotel", label: "Khách sạn" },
  { value: "inn", label: "Nhà trọ nhỏ" },
  { value: "lodge", label: "Nhà nghỉ dưỡng" },
  { value: "motel", label: "Nhà nghỉ ven đường" },
  { value: "resort", label: "Khu nghỉ dưỡng" },
  { value: "riad", label: "Riad (nhà truyền thống Ma-rốc)" },
  { value: "ryokan", label: "Ryokan (nhà trọ kiểu Nhật)" },
  { value: "tent", label: "Lều" },
  { value: "villa", label: "Biệt thự" },
];

export const TIME_RANGE = [
  { value: "previous", title: "Kỳ trước" },
  { value: "yearly", title: "Năm ngoái" },
];

export const QICK_DATE_RANGES = [
  { value: 7, title: "7 ngày" },
  { value: 30, title: "30 ngày" },
  { value: 60, title: "60 ngày" },
  { value: 90, title: "90 ngày" },
  { value: 0, title: "Tuỳ chọn" },
];

export const SHORT_TIME_RANGES = [
  { value: -1, title: "Hôm qua" },
  { value: 0, title: "Hôm nay" },
  { value: 1, title: "Ngày mai" },
];

export const palette = {
  mixed: [
    "#90CAF9",
    "#4CAF50",
    "#EF5350",
    "#7E57C2",
    "#CC6633",
    "#dcff5cff",
    "#21ffaaff",
  ],
  pie: [
    "#2962FF",
    "#00C853",
    "#FFAB00",
    "#D50000",
    "#9C27B0",
    "#CC6633",
    "#dcff5cff",
    "#21ffaaff",
  ],
};
export const statusOptions = ["Hủy", "Mới", "Xác nhận", "Yêu cầu", "Không đến"];
export const otaChannels = [
  "Walk-in",
  "Từ đối tác",
  "BookingCom",
  "Agoda",
  "Expedia",
  "Airbnb",
  "Ctrip",
];

export const otaOptions = [
  { value: "Walk-in", label: "Walk-in" },
  { value: "Từ đối tác", label: "Từ đối tác" },
  { value: "BookingCom", label: "BookingCom" },
  { value: "Agoda", label: "Agoda" },
  { value: "Expedia", label: "Expedia" },
  { value: "Airbnb", label: "Airbnb" },
  { value: "Ctrip", label: "Ctrip" },
];

export const sortOptions = [
  {
    title: "Thời gian cập nhật (mới → cũ)",
    value: { field: "updated_at", order: "DESC" },
  },
  {
    title: "Thời gian cập nhật (cũ → mới)",
    value: { field: "updated_at", order: "ASC" },
  },
  {
    title: "Tên OTA (Z → A)",
    value: { field: "ota_name", order: "DESC" },
  },
  {
    title: "Tên OTA (A → Z)",
    value: { field: "ota_name", order: "ASC" },
  },
];
export const sortRoomOptions = [
  {
    title: "Thời gian cập nhật (mới → cũ)",
    value: { field: "updated_at", order: "DESC" },
  },
  {
    title: "Thời gian cập nhật (cũ → mới)",
    value: { field: "updated_at", order: "ASC" },
  },
  { title: "Tên phòng (DESC)", value: { field: "name", order: "DESC" } },
  { title: "Tên phòng (ASC)", value: { field: "name", order: "ASC" } },
];

export const dateTypeOptions = [
  { title: "Ngày tạo", value: "created_at" },
  { title: "Ngày nhận phòng", value: "check_in_date" },
  { title: "Ngày trả phòng", value: "check_out_date" },
];

export const bookingStatusOptions = [
  "Mới",
  "Xác nhận",
  "Hủy",
  "Yêu cầu",
  "Không đến",
];
export const paymentStatusOptions = [
  "Chưa thanh toán",
  "Đã thanh toán",
  "Đã cọc",
  "Chờ thanh toán",
];

export const monthOptions = [
  { title: "Tháng 1", value: 1 },
  { title: "Tháng 2", value: 2 },
  { title: "Tháng 3", value: 3 },
  { title: "Tháng 4", value: 4 },
  { title: "Tháng 5", value: 5 },
  { title: "Tháng 6", value: 6 },
  { title: "Tháng 7", value: 7 },
  { title: "Tháng 8", value: 8 },
  { title: "Tháng 9", value: 9 },
  { title: "Tháng 10", value: 10 },
  { title: "Tháng 11", value: 11 },
  { title: "Tháng 12", value: 12 },
];

export const currencyOptions = [
  { label: "Việt Nam Đồng (VND)", value: "VND" },
  // { label: "U.S. Dollar (USD)", value: "USD" },
  // { label: "Japanese Yen (JPY)", value: "JPY" },
];

export const otaLogos = {
  bookingcom: "/images/bookingcom.png",
  ctrip: "/images/ctrip.png",
  expedia: "/images/expedia.png",
  airbnb: "/images/airbnb.png",
  agoda: "/images/agoda.png",
};

export const ALL_OPTION = { title: "Tất cả", value: null };

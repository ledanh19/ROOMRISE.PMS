export const headerCheckInCheckOutListReport = [
  { key: "code", title: "Tham khảo #", width: 160, align: "center" },
  { key: "guestName", title: "Tên khách", width: 300, align: "center" },
  { key: "guestNumber", title: "Số lượng khách", width: 160, align: "center" },
  { key: "roomType", title: "Loại phòng", width: 160, align: "center" },
  { key: "roomName", title: "Tên phòng", width: 160, align: "center" },
  { key: "checkIn", title: "Đến (ngày|giờ)", width: 160, align: "center" },
  {
    key: "checkOut",
    title: "Khởi hành (ngày|giờ)",
    width: 160,
    align: "center",
  },
  { key: "source", title: "Nguồn đặt phòng", width: 160, align: "center" },
  { key: "status", title: "Trạng thái", width: 160, align: "center" },
];

export const headerCustomerInformationReport = [
  { key: "id", title: "Id", width: 160, align: "center" },
  { key: "date", title: "Ngày", width: 160, align: "center" },
  { key: "name", title: "Tên", width: 300, align: "center" },
  { key: "dob", title: "Ngày sinh", width: 160, align: "center" },
  { key: "email", title: "email", width: 160, align: "center" },
  { key: "phone", title: "Điện thoại", width: 160, align: "center" },
  { key: "city", title: "City", width: 160, align: "center" },
  { key: "country", title: "Quốc gia", width: 160, align: "center" },
  { key: "nationality", title: "Quốc tịch", width: 160, align: "center" },
  { key: "type", title: "Nguồn", width: 160, align: "center" },
  { key: "address", title: "Địa chỉ", width: 160, align: "center" },
];

export const headerDailyPaymentReportTable1 = [
  { key: "addWhen", title: "Thêm vào lúc", width: 160, align: "center" },
  { key: "collectBy", title: "Bởi người dùng", width: 300, align: "center" },
  { key: "amountText", title: "Số tiền", width: 160, align: "center" },
  { key: "bookingRoom", title: "Đặt phòng #", width: 160, align: "center" },
  { key: "guestName", title: "Tên khách", width: 300, align: "center" },
  { key: "checkInDate", title: "Nhận phòng", width: 160, align: "center" },
  { key: "checkOutDate", title: "Trả phòng", width: 160, align: "center" },
  { key: "roomName", title: "Tên phòng", width: 160, align: "center" },
];

export const headerDailyPaymentReportTable2 = [
  {
    key: "method",
    title: "Phương thức thanh toán",
    width: 160,
    align: "center",
  },
  { key: "vnd", title: "VND/ Dong", width: 160, align: "center" },
];

export const headerInternalGuestListReport = [
  { key: "code", title: "Tham khảo #", width: 140, align: "center" },
  { key: "guestName", title: "Tên khách", width: 220, align: "center" },
  { key: "roomType", title: "Loại phòng", width: 180, align: "center" },
  { key: "roomName", title: "Tên phòng", width: 160, align: "center" },
  { key: "guestNumber", title: "Số lượng khách", width: 140, align: "center" },
  { key: "checkIn", title: "Đến (ngày|giờ)", width: 180, align: "center" },
  {
    key: "checkOut",
    title: "Khởi hành (ngày|giờ)",
    width: 200,
    align: "center",
  },
  { key: "source", title: "Nguồn đặt phòng", width: 180, align: "center" },
  { key: "status", title: "Trạng thái", width: 140, align: "center" },
];

export const headerBookingSourceReport = [
  { key: "month", title: "Tháng", width: 120, align: "center" },
  { key: "totalRevenue", title: "Doanh thu", width: 160, align: "end" },
  { key: "bookingCom", title: "BookingCom", width: 120, align: "end" },
  { key: "walkIn", title: "Walk-in", width: 120, align: "end" },
  { key: "offline", title: "Từ đối tác", width: 120, align: "end" },
  { key: "agoda", title: "Agoda", width: 120, align: "end" },
  { key: "expedia", title: "expedia", width: 120, align: "end" },
  { key: "airbnb", title: "Airbnb", width: 120, align: "end" },
  { key: "ctrip", title: "Ctrip", width: 120, align: "end" },
  // { key: "cmRevenue", title: "CM Revenue", width: 140, align: "end" },
];

export const headerOccupancyReport = [
  { key: "date", title: "", width: 140, align: "center" },
  {
    key: "occupiedRooms",
    title: "Số phòng có khách",
    width: 200,
    align: "center",
  },
  { key: "adults", title: "Số người lớn", width: 140, align: "center" },
  { key: "children", title: "Số trẻ em", width: 150, align: "center" },
  {
    key: "checkInRooms",
    title: "Số phòng nhận khách (Check-in)",
    width: 190,
    align: "center",
  },
  {
    key: "checkOutRooms",
    title: "Số phòng trả khách (Check-out)",
    width: 200,
    align: "center",
  },
  {
    key: "availableRooms",
    title: "Số phòng còn trống",
    width: 200,
    align: "center",
  },
  { key: "totalRooms", title: "Tổng số phòng", width: 140, align: "center" },
  {
    key: "occupancyPercentage",
    title: "Tỷ lệ lấp đầy",
    width: 190,
    align: "center",
  },
];

export const headerBookingReport = [
  {
    key: "otaReservationCode",
    title: "Tham khảo #",
    width: 160,
    align: "start",
  },
  { key: "fullName", title: "Tên khách", width: 180, align: "start" },
  { key: "createdAt", title: "Ngày tạo", width: 120, align: "center" },
  { key: "checkInDate", title: "Nhận phòng", width: 120, align: "center" },
  { key: "checkOutDate", title: "Trả phòng", width: 120, align: "center" },
  {
    key: "customerPaymentAmount",
    title: "Giá trị phòng",
    width: 140,
    align: "end",
    money: true,
  },
  {
    key: "commissionFee",
    title: "Thuế/Phí (Commission)",
    width: 160,
    align: "end",
    money: true,
  },
  {
    key: "otaFee",
    title: "Phụ phí (OTA fee)",
    width: 140,
    align: "end",
    money: true,
  },
  {
    key: "totalAmount",
    title: "Tổng cộng",
    width: 150,
    align: "end",
    money: true,
  },
  { key: "bookingStatus", title: "Trạng thái", width: 140, align: "center" },
  { key: "paymentStatus", title: "Thanh toán", width: 140, align: "center" },
];

export const headerPerformanceReport = [
  { key: "otaName", title: "Nguồn", width: 140, align: "center" },
  { key: "numberBookings", title: "Đặt phòng", width: 220, align: "center" },
  { key: "numberGuests", title: "Khách", width: 180, align: "center" },
  { key: "numberNights", title: "Đêm", width: 160, align: "center" },
  { key: "avgNights", title: "Số đêm trung bình", width: 140, align: "center" },
  { key: "totalRevenue", title: "Tổng doanh thu", width: 140, align: "center" },
  {
    key: "avgTotalBookingValue",
    title: "Trung bình giá trị đặt phòng",
    width: 140,
    align: "center",
  },
  {
    key: "avgTotalNightlyValue",
    title: "Trung bình giá trị mỗi đêm",
    width: 140,
    align: "center",
  },
  {
    key: "avgLeadTime",
    title: "Thời gian đặt trước trung bình",
    width: 140,
    align: "center",
  },
];

export const headerRevenueDetail = [
  {
    key: "otaReservationCode",
    title: "Mã đặt phòng OTA",
    width: 140,
    align: "center",
  },
  { key: "customerName", title: "Tên khách", width: 140, align: "center" },
  { key: "checkInDate", title: "Ngày nhận phòng", width: 140, align: "center" },
  { key: "checkOutDate", title: "Ngày trả phòng", width: 140, align: "center" },
  { key: "nights", title: "Số đêm", width: 140, align: "center" },
  { key: "guestQuantity", title: "Số khách", width: 140, align: "center" },
  {
    key: "customerPaymentAmount",
    title: "Khách thanh toán",
    width: 140,
    align: "center",
  },
  { key: "commissionFee", title: "Hoa hồng", width: 140, align: "center" },
  { key: "otaFee", title: "Phí OTA", width: 140, align: "center" },
  { key: "currency", title: "Tiền tệ", width: 140, align: "center" },
  { key: "totalAmount", title: "Tổng tiền", width: 140, align: "center" },
  { key: "propertyName", title: "Cơ sở lưu trú", width: 160, align: "center" },
  { key: "otaName", title: "Nguồn đặt phòng", width: 160, align: "center" },
];

export const headerRevenueDetailTotal = [
  { key: "currency", title: "Tiền tệ", width: 140, align: "center" },
  {
    key: "bookingQuantity",
    title: "Số lượng đặt phòng",
    width: 140,
    align: "center",
  },
  {
    key: "guestQuantity",
    title: "Số lượng khách",
    width: 140,
    align: "center",
  },
  {
    key: "customerPaymentAmount",
    title: "Khách thanh toán",
    width: 160,
    align: "center",
  },
  { key: "commissionFee", title: "Hoa hồng", width: 160, align: "center" },
  { key: "otaFee", title: "Phí OTA", width: 160, align: "center" },
  { key: "totalAmount", title: "Tổng tiền", width: 160, align: "center" },
];

export const headerRevenueBooking = [
  { key: "date", title: "Ngày", width: 140, align: "center" },
  {
    key: "bookingQuantity",
    title: "Số lượng đặt phòng",
    width: 140,
    align: "center",
  },
  {
    key: "customerPaymentAmount",
    title: "Khách thanh toán",
    width: 160,
    align: "center",
  },
  { key: "commissionFee", title: "Hoa hồng", width: 160, align: "center" },
  { key: "otaFee", title: "Phí OTA", width: 160, align: "center" },
  { key: "totalAmount", title: "Tổng tiền", width: 160, align: "center" },
  { key: "currency", title: "Tiền tệ", width: 140, align: "center" },
];

export const headerRevenueBookingSourceReport = [
  { key: "date", title: "Ngày", width: 120, align: "center" },
  { key: "totalRevenue", title: "Danh thu", width: 160, align: "end" },
  { key: "bookingCom", title: "BookingCom", width: 120, align: "end" },
  { key: "walkIn", title: "Walk-in", width: 120, align: "end" },
  { key: "offline", title: "Từ đối tác", width: 120, align: "end" },
  { key: "agoda", title: "Agoda", width: 120, align: "end" },
  { key: "expedia", title: "expedia", width: 120, align: "end" },
  { key: "airbnb", title: "Airbnb", width: 120, align: "end" },
  { key: "ctrip", title: "Ctrip", width: 120, align: "end" },
];

export const headerDailySaleReport = [
  { key: "roomType", title: "Loại phòng", align: "center" },
  { key: "roomRevenue", title: "Doanh thu phòng", align: "end", width: 160 },
  { key: "roomNights", title: "Số đêm phòng", align: "end", width: 120 },
  { key: "occ", title: "OCC", align: "end", width: 90 },
  { key: "adr", title: "ADR", align: "end", width: 120 },
  { key: "revpar", title: "Revpar", align: "end", width: 120 },
  { key: "extraRevenue", title: "Doanh thu bổ sung", align: "end", width: 160 },
  { key: "totalRevenue", title: "Tổng doanh thu", align: "end", width: 160 },
  { key: "trevpar", title: "Trevpar", align: "end", width: 120 },
];

export const headerPerformanceByRoomType = [
  { key: "roomType", title: "Loại phòng", align: "center" },
  { key: "occ", title: "OCC", width: 150, align: "end" },
  { key: "adr", title: "ADR", width: 120, align: "end" },
  { key: "revpar", title: "RevPAR", width: 120, align: "end" },
  { key: "trevpar", title: "TRevPAR", width: 120, align: "end" },
  { key: "roomRevenue", title: "Doanh thu phòng", width: 180, align: "end" },
  { key: "roomRevenuePct", title: "%", width: 90, align: "end" },
  {
    key: "commissionFee",
    title: "Doanh thu dịch vụ thêm",
    width: 200,
    align: "end",
  },
  { key: "otaFee", title: "Doanh thu dịch vụ thêm", width: 200, align: "end" },
  { key: "serviceRevenuePercent", title: "%", width: 90, align: "end" },
  { key: "totalRevenue", title: "Tổng doanh thu", width: 160, align: "end" },
  { key: "totalRevenuePct", title: "%", width: 90, align: "end" },
];

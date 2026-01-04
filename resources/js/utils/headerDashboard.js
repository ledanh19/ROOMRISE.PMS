export const bookingHeaders = [
  { title: "Mã đặt phòng", key: "code", sortable: false },
  { title: "Property", key: "property", sortable: false },
  { title: "Nguồn", key: "otaName", sortable: false },
  { title: "Loại phòng", key: "room", sortable: false },
  { title: "Tên khách", key: "customer", sortable: false },
  { title: "Nhận phòng", key: "checkInDateShort", sortable: false },
  { title: "Trả phòng", key: "checkOutDateShort", sortable: false },
  { title: "Số đêm", key: "nights", sortable: false },
  { title: "Thời gian đặt trước", key: "leadTime", sortable: false },
  { title: "Doanh thu", key: "amount", sortable: false },
  { title: "Trạng thái", key: "bookingStatus", sortable: false },
];

export const revenueHeaders = [
  { title: "Nguồn/Đối tác", key: "otaName", sortable: false },
  { title: "Doanh thu", key: "currentRevenue", sortable: false },
  {
    title: "% trên tổng doanh thu",
    key: "percentTotalRevenue",
    sortable: false,
  },
  { title: "Growth doanh thu", key: "percentGrowthRevenue", sortable: false },
  { title: "Số booking", key: "currentBookings", sortable: false },
  {
    title: "% trên tổng booking",
    key: "percentTotalBookings",
    sortable: false,
  },
  { title: "Growth booking", key: "percentGrowthBookings", sortable: false },
];

export const performanceHeaders = [
  { title: "Property", key: "propertyName", sortable: false },
  { title: "Occupancy", key: "OCC", sortable: false },
  { title: "ADR", key: "ADR", sortable: false },
  { title: "RevPAR", key: "RevPAR", sortable: false },
  { title: "LOS", key: "LOS", sortable: false },
  { title: "Lead Time", key: "leadTime", sortable: false },
  { title: "Doanh thu", key: "revenue", sortable: false },
  { title: "Growth", key: "percentGrowthRevenue", sortable: false },
];

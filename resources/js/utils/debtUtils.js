//Doanh thu
export function totalRevenue(partner) {
  return partner.customers?.reduce((sum, customer) => {
    return (
      sum +
      customer.bookings?.reduce(
        (s, b) => s + Number(b.customer_payment_amount || 0),
        0
      )
    );
  }, 0);
}
//hoa hồng
export function totalCommission(partner) {
  return (
    partner.customers?.reduce((sum, customer) => {
      return (
        sum +
        customer.bookings?.reduce(
          (s, b) => s + Number(b.commission_fee || 0),
          0
        )
      );
    }, 0) || 0
  );
}

//đã xử lý
export function totalProcessed(partner) {
  return partner.customers?.reduce((sum, customer) => {
    return (
      sum +
      customer.bookings?.reduce((s, b) => {
        return (
          s + (b.income_expense_id ? Number(b.customer_payment_amount || 0) : 0)
        );
      }, 0)
    );
  }, 0);
}
export function netDebtAfterCommission(partner) {
  return calculateNetDebt(partner).netDebt;
}

export function totalReceivableAfterCommission(partner) {
  return calculateNetDebt(partner).netDebt;
}

export function getDebtStatus(partner) {
  const net = netDebtAfterCommission(partner);
  const revenue = totalRevenue(partner); 
  if (revenue === 0) return "Không có công nợ";
  if (net === 0) return "Đã thanh toán";
  if (net < 0) return "Cần trả";
  if (net > 0) return "Còn nợ";

  return "Không xác định";
}

export function getDebtStatusColor(status) {
  switch (status) {
    case "Đã thanh toán":
      return "success";
    case "Cần trả":
      return "warning";
    case "Còn nợ":
      return "error";
    default:
      return "default";
  }
}
export function calculateNetDebt(partner) {
  const partnerCollected =
    partner.customers?.reduce((sum, customer) => {
      return (
        sum +
        customer.bookings?.reduce((s, b) => {
          return b.payment_type === "Partner Collect"
            ? s + Number(b.customer_payment_amount || 0)
            : s;
        }, 0)
      );
    }, 0) || 0;

  const totalCommission =
    partner.customers?.reduce((sum, customer) => {
      return (
        sum +
        customer.bookings?.reduce(
          (s, b) => s + Number(b.commission_fee || 0),
          0
        )
      );
    }, 0) || 0;

  return {
    partnerCollected,
    totalCommission,
    netDebt: partnerCollected - totalCommission,
  };
}

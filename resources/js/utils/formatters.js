import { format } from "date-fns";
import { CURRENCIES } from "./constants";

// export const formatDate = (dateString) => {
//   if (!dateString) return "-";
//   return format(new Date(dateString), "dd-MM-yyyy h:mm a");
// };

export const formatDate = (dateString) => {
  if (!dateString) return "-";
  return format(new Date(dateString), "dd-MM-yyyy");
};

export const formatCurrency = (value, currency = "VND") => {
  if (!value && value !== 0) return "-";

  const currencyInfo = CURRENCIES.find((c) => c.value === currency) || {
    value: currency,
    locale: "vi-VN",
  }; // Fallback

  try {
    return new Intl.NumberFormat(currencyInfo.locale, {
      style: "currency",
      currency: currencyInfo.value,
    }).format(value);
  } catch (error) {
    return value.toLocaleString() + " " + currencyInfo.value;
  }
};

export interface BookingFormIds {
  widgetId: string;
  checkInId: string;
  checkOutId: string;
  guestsId: string;
}

export function getBookingFormIds(prefix = ''): BookingFormIds {
  return {
    widgetId: `${prefix}booking-widget`,
    checkInId: `${prefix}check-in-date`,
    checkOutId: `${prefix}check-out-date`,
    guestsId: `${prefix}guests-count`,
  };
}

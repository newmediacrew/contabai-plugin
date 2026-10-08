// Exposes the self-hosted Vanilla Calendar Pro ESM build on window so the plugin's
// classic (non-module) Alpine components can use it. See render_search() enqueue.
// v3.4+ moved multi-month display into the `months` extension, so expose it too.
import { Calendar, months } from './vanilla-calendar/index.js?v=3.4.0';
window.VanillaCalendar = Calendar;
window.VanillaCalendarMonths = months;

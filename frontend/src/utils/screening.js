// 公益放映排期相关的展示工具函数

const WEEKDAYS = ['周日', '周一', '周二', '周三', '周四', '周五', '周六'];

/**
 * "2026-09-20" -> "09-20 周日"
 */
export function formatScreeningDate(dateStr) {
  if (!dateStr) return '';
  const d = new Date(`${dateStr}T00:00:00`);
  if (Number.isNaN(d.getTime())) return dateStr;
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${mm}-${dd} ${WEEKDAYS[d.getDay()]}`;
}

/**
 * "14:00:00" -> "14:00"
 */
export function formatScreeningTime(timeStr) {
  if (!timeStr) return '';
  return String(timeStr).slice(0, 5);
}

/**
 * 将排期拆分为近期场次（升序）与历史场次（降序）。
 * 后端会下发 is_upcoming 标记，过期场次自动归入历史。
 */
export function splitScreenings(screenings) {
  const list = Array.isArray(screenings) ? screenings : [];
  const key = (s) => `${s.screening_date} ${s.start_time || ''}`;
  const upcoming = list.filter((s) => s.is_upcoming).sort((a, b) => key(a).localeCompare(key(b)));
  const past = list.filter((s) => !s.is_upcoming).sort((a, b) => key(b).localeCompare(key(a)));
  return { upcoming, past };
}

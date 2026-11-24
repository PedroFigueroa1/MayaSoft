export function formatDate(dateString) {
  if (!dateString) return '';
  // Se asume formato YYYY-MM-DD
  const [year, month, day] = dateString.split('-');
  return `${day}/${month}/${year}`;
}

export function formatDateTime(dateTimeString) {
  if (!dateTimeString) return '';
  // Se asume formato YYYY-MM-DD HH:MM:SS
  const [date, time] = dateTimeString.split(' ');
  const [year, month, day] = date.split('-');
  const [hour, minute] = time.split(':');
  return `${day}/${month}/${year} ${hour}:${minute}`;
}

function timeDifferenceFromNow(previous) {
  const current = parseInt(Date.now());
  previous = parseInt(previous) * 1000;

  let msPerMinute = 60 * 1000;
  let msPerHour = msPerMinute * 60;
  let msPerDay = msPerHour * 24;
  let msPerMonth = msPerDay * 30;
  let msPerYear = msPerDay * 365;

  let elapsed = current - previous;
  let time = 0;

  if (elapsed < msPerMinute) {
    time = Math.round(elapsed / 1000);
    return time !== 1 ? Math.abs(time) + ' seconds ago' : Math.abs(time) + ' second ago';
  } else if (elapsed < msPerHour) {
    time = Math.round(elapsed / msPerMinute);
    return time !== 1 ? time + ' minutes ago' : time + ' minute ago';
  } else if (elapsed < msPerDay) {
    time = Math.round(elapsed / msPerHour);
    return time !== 1 ? time + ' hours ago' : time + ' hour ago';
  } else if (elapsed < msPerMonth) {
    time = Math.round(elapsed / msPerDay);
    return time !== 1 ? time + ' days ago' : time + ' day ago';
  } else if (elapsed < msPerYear) {
    time = Math.round(elapsed / msPerMonth);
    return time !== 1 ? time + ' months ago' : time + ' month ago';
  } else {
    time = Math.round(elapsed / msPerYear);
    return time !== 1 ? time + ' years ago' : time + ' year ago';
  }
}

function formatShortDate(timestamp) {
  const date = new Date(timestamp * 1000);
  return date.getDate() + ' ' + getMonthByIndex(date.getMonth()).toUpperCase() + ' ' + date.getFullYear();
}

function getMonthByIndex(index) {
  const months = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December'
  ];

  return months[index];
}

export {timeDifferenceFromNow, getMonthByIndex, formatShortDate}

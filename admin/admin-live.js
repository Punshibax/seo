(() => {
  const badge = document.querySelector('#admin-live-count');
  if (!badge) return;
  let previous = null;
  async function refresh() {
    try {
      const response = await fetch('notifications', { credentials: 'same-origin', cache: 'no-store' });
      if (!response.ok) return;
      const data = await response.json();
      badge.textContent = data.total > 0 ? `${data.total} needs attention` : 'All caught up';
      badge.classList.toggle('has-notifications', data.total > 0);
      if (previous !== null && (data.new_leads > previous.new_leads || data.unread_replies > previous.unread_replies || data.pending_bookings > previous.pending_bookings) && document.visibilityState === 'visible') {
        let toast = document.querySelector('#admin-live-toast');
        if (!toast) {
          toast = document.createElement('div');
          toast.id = 'admin-live-toast';
          toast.className = 'admin-live-toast';
          toast.setAttribute('role', 'status');
          document.body.appendChild(toast);
        }
        const details = [];
        if (data.new_leads) details.push(`${data.new_leads} new enquiries`);
        if (data.unread_replies) details.push(`${data.unread_replies} student replies`);
        if (data.pending_bookings) details.push(`${data.pending_bookings} counselling requests`);
        toast.textContent = `New activity: ${details.join(', ')}.`;
      }
      previous = { new_leads: data.new_leads, unread_replies: data.unread_replies, pending_bookings: data.pending_bookings };
    } catch (_) {}
  }
  refresh();
  window.setInterval(refresh, 15000);
})();

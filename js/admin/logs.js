// ✅ Fetch and render all logs with filters
async function fetchLogs() {
  const role = document.getElementById('roleFilter').value;
  const action = document.getElementById('actionFilter').value;
  const sort = document.getElementById('dateSort').value;
  const search = document.getElementById('logSearch').value;

  try {
    const res = await fetch(`../backend/admin/logs/fetch-logs.php?role=${role}&action=${action}&sort=${sort}&search=${search}`);
    const data = await res.json();
    const tbody = document.getElementById('records-table-body');
    tbody.innerHTML = '';

    if (data.success && data.logs.length > 0) {
      data.logs.forEach((log, i) => {
        const row = document.createElement('tr');
        row.classList.add('log-row');
        row.dataset.userId = log.user_id;
        row.dataset.userName = log.username;
        row.dataset.role = log.role;
        row.title = 'Click to view full log history';

        row.innerHTML = `
          <td>${i + 1}</td>
          <td>${log.created_at}</td>
          <td>${log.username}</td>
          <td>${log.role}</td>
          <td>${log.action}</td>
          <td>${log.target_id ?? '-'}</td>
          <td>${log.description}</td>
          <td>${log.ip_address}</td>
        `;
        tbody.appendChild(row);
      });
    } else {
      tbody.innerHTML = `<tr><td colspan="8" style="text-align: center;">No logs found.</td></tr>`;
    }
  } catch (err) {
    console.error(err);
    showToast("Failed to fetch logs", 'error');
  }
}

// ✅ Fetch logs for a specific user and show in modal
async function fetchUserLogs(userId, username) {
  try {
    const res = await fetch(`../backend/admin/logs/fetch-user-logs.php?user_id=${userId}`);
    const data = await res.json();
    const tbody = document.getElementById('userLogsBody');
    const title = document.getElementById('userLogsTitle');
    tbody.innerHTML = '';
    title.textContent = `Logs for ${username}`;

    if (data.success && data.logs.length > 0) {
      data.logs.forEach((log, i) => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${i + 1}</td>
          <td>${log.created_at}</td>
          <td>${log.action}</td>
          <td>${log.description}</td>
          <td>${log.ip_address}</td>
        `;
        tbody.appendChild(row);
      });
    } else {
      tbody.innerHTML = `<tr><td colspan="5" style="text-align: center;">No logs found for this user.</td></tr>`;
    }

    // ✅ Open modal
    const modal = document.getElementById('userLogsModal');
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');

  } catch (err) {
    console.error(err);
    showToast("Failed to fetch user logs", 'error');
  }
}

// ✅ Fetch users by selected role (only admin/healthcare)
async function fetchUsersByRole(role) {
  const container = document.getElementById('roleUsersList');
  container.innerHTML = '';

  if (!role) return;

  try {
    const res = await fetch(`../backend/admin/logs/fetch-logs.php?fetch_users_by_role=${role}`);
    const data = await res.json();

    if (!data.success) {
      container.innerHTML = `<em>${data.message}</em>`;
    } else if (data.users.length > 0) {
      data.users.forEach(user => {
        const btn = document.createElement('button');
        btn.className = 'role-user-btn';
        btn.textContent = user.username;
        btn.onclick = () => fetchUserLogs(user.id, user.username);
        container.appendChild(btn);
      });
    } else {
      container.innerHTML = '<em>No users found for this role.</em>';
    }
  } catch (err) {
    console.error(err);
    showToast('Failed to fetch users for this role', 'error');
  }
}

// ✅ Row click → open modal + fetch logs + users (admin/healthcare only)
document.addEventListener('click', (e) => {
  const row = e.target.closest('.log-row');
  if (row) {
    const userId = row.dataset.userId;
    const userName = row.dataset.userName;
    const role = row.dataset.role;

    fetchUserLogs(userId, userName);

    if (role === 'admin' || role === 'healthcare') {
      fetchUsersByRole(role);
    }
  }
});

// ✅ Close modal on X click
document.getElementById('closeUserLogs').onclick = () => {
  document.getElementById('userLogsModal').style.display = 'none';
  document.body.classList.remove('modal-open');
};

// ✅ Close modal when clicking outside
window.addEventListener('click', (e) => {
  const modal = document.getElementById('userLogsModal');
  if (e.target === modal) {
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
  }
});

// ✅ Show toast
function showToast(message, type = 'success') {
  const toast = document.getElementById('toast');
  const toastMessage = document.getElementById('toastMessage');
  toastMessage.textContent = message;
  toast.className = `toast ${type}`;
  toast.style.display = 'block';
  setTimeout(() => {
    toast.style.display = 'none';
  }, 3000);
}

// ✅ Filter listeners
document.getElementById('roleFilter').onchange = () => {
  const role = document.getElementById('roleFilter').value;
  fetchLogs();
  fetchUsersByRole(role);
};
document.getElementById('actionFilter').onchange = fetchLogs;
document.getElementById('dateSort').onchange = fetchLogs;
document.getElementById('logSearch').oninput = fetchLogs;

// ✅ On page load
document.addEventListener('DOMContentLoaded', () => {
  fetchLogs();
  document.getElementById('userLogsModal').style.display = 'none';
});

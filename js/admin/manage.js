document.addEventListener("DOMContentLoaded", () => {
  const isSpecialAdmin = document.getElementById('isSpecialAdmin')?.value === '1';
  const currentAdminId = parseInt(document.getElementById('currentAdminId')?.value || "0");

  const tableBody = document.getElementById("records-table-body");
  const tableHeader = document.getElementById("table-header");
  const paginationContainer = document.getElementById("pagination");
  const search = document.getElementById("reportSearch");
  const roleFilter = document.getElementById("roleFilter");
  const region = document.getElementById("regionFilter");
  const regionWrapper = document.getElementById("regionFilterWrapper");
  const dateFilter = document.getElementById("dateFilter");
  const title = document.getElementById("pageTitle");

  let savedFilters = {};
  try {
    savedFilters = JSON.parse(localStorage.getItem("adminFilters") || "{}");
  } catch {
    localStorage.removeItem("adminFilters");
  }

  if (isSpecialAdmin && savedFilters.role) roleFilter.value = savedFilters.role;
  if (savedFilters.search) search.value = savedFilters.search;
  if (savedFilters.region) region.value = savedFilters.region;
  if (savedFilters.sort) dateFilter.value = savedFilters.sort;

  function toggleRegionFilter(forceRole = roleFilter.value) {
    const isHealthcare = forceRole === "healthcare";
    regionWrapper.style.display = isHealthcare ? "block" : "none";
    renderHeader(forceRole);
    title.textContent = isHealthcare ? "Healthcare Staff" : "Admins";
  }

  if (!isSpecialAdmin) {
    roleFilter.value = "healthcare";
    roleFilter.disabled = true;
  }
  toggleRegionFilter();

  roleFilter.addEventListener("change", () => {
    saveFilters();
    toggleRegionFilter();
    loadPage(1);
  });

  [search, region, dateFilter].forEach(input => {
    input.addEventListener("change", () => {
      saveFilters();
      loadPage(1);
    });
  });

  let debounceTimer;
  search.addEventListener("input", () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
      saveFilters();
      loadPage(1);
    }, 300);
  });

  function saveFilters() {
    localStorage.setItem("adminFilters", JSON.stringify({
      role: roleFilter.value,
      region: region.value,
      search: search.value,
      sort: dateFilter.value
    }));
  }

  function renderHeader(role) {
    const common = `
      <th>#</th>
      <th>Full Name</th>
      <th>Username</th>
      <th>Email</th>`;
    const extra = role === "healthcare" ? `<th>Region</th>` : "";
    const action = `<th>Registered</th><th>Action</th>`;
    tableHeader.innerHTML = `<tr>${common}${extra}${action}</tr>`;
  }

  function formatDate(dateStr) {
    const d = new Date(dateStr);
    return isNaN(d.getTime()) ? "-" : d.toLocaleDateString();
  }

  function loadPage(page = 1) {
    const params = new URLSearchParams({
      search: search.value,
      role: roleFilter.value,
      region: region.value,
      sort: dateFilter.value,
      page
    });

    const colspan = (roleFilter.value === "healthcare") ? 7 : 6;
    tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center; padding:30px 0;">⏳ Loading...</td></tr>`;

    fetch("/eyecheck/backend/admin/get.php?" + params, { credentials: 'include' })
      .then(res => res.json())
      .then(response => {
        if (!response.success) {
          tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">🚫 Error loading data.</td></tr>`;
          return;
        }
        renderTable(response.data, roleFilter.value, response.currentPage, response.perPage);
        renderPagination(response.total, response.perPage, response.currentPage);
        if (roleFilter.value === "healthcare") updateRegionFilter(response.regions);
      })
      .catch(() => {
        tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">⚠️ Network error.</td></tr>`;
      });
  }

  function renderTable(data, role, currentPage, perPage) {
    const colspan = role === "healthcare" ? 7 : 6;
    if (data.length === 0) {
      const msg = role === 'admin' ? "😐 No admins found." : "😐 No staff found.";
      tableBody.innerHTML = `<tr><td colspan="${colspan}" style="text-align:center;">${msg}</td></tr>`;
      return;
    }

    tableBody.innerHTML = data.map((user, index) => {
      const createdAt = formatDate(user.created_at);
      let row = `
        <td>#${(currentPage - 1) * perPage + index + 1}</td>
        <td>${user.full_name}</td>
        <td>${user.username}</td>
        <td>${user.email}</td>`;

      if (role === "healthcare") {
        row += `<td>${user.healthcare_region ?? '-'}</td>`;
      }

      row += `<td>${createdAt}</td>
        <td style="white-space: nowrap;">
          <a href="form.php?id=${user.id}&role=${role}" class="action-btn edit-btn" title="Edit" aria-label="Edit user">
            <i class="fas fa-edit"></i>
          </a>
          <a href="change-password.php?id=${user.id}&role=${role}" class="action-btn password-btn" title="Change Password" aria-label="Change password">
            <i class="fas fa-key"></i>
          </a>`;

      const isSelf = user.id === currentAdminId;

      if (isSpecialAdmin && !isSelf) {
        row += `<button class="action-btn open-delete-modal" data-id="${user.id}" data-role="${user.role}" data-type="user" title="Delete" aria-label="Delete user">
                  <i class="fas fa-trash-alt"></i>
                </button>`;
      } else {
        row += `<button class="action-btn disabled-delete-btn" title="You don’t have permission to delete this user." aria-label="No permission">
                  <i class="fas fa-trash-alt"></i>
                </button>`;
      }

      row += `</td>`;
      return `<tr>${row}</tr>`;
    }).join("");

    bindDeleteButtons();
  }

  function renderPagination(total, perPage, currentPage) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const a = document.createElement("a");
      a.textContent = i;
      a.href = "#";
      if (i === currentPage) a.classList.add("active");
      a.addEventListener("click", e => {
        e.preventDefault();
        loadPage(i);
      });
      paginationContainer.appendChild(a);
    }
  }

  function updateRegionFilter(regions) {
    region.innerHTML = "<option value='all'>All</option>";
    regions.forEach(r => {
      const opt = document.createElement("option");
      opt.value = r;
      opt.textContent = r;
      region.appendChild(opt);
    });
    if (savedFilters.region) region.value = savedFilters.region;
  }

  function openDeleteModal(id, role, type = 'user') {
    const overlay = document.querySelector(".delete-modal-overlay");
    overlay.classList.add("active");

    const confirmBtn = overlay.querySelector(".delete-confirm-btn");
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = overlay.querySelector(".delete-confirm-btn");

    newConfirmBtn.addEventListener("click", () => {
      const bodyData = new URLSearchParams({ target_user_id: id, role: 'admin'});
      fetch("/eyecheck/backend/shared/delete-handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: bodyData,
        credentials: "include"
      })
        .then(res => res.json())
        .then(data => {
          overlay.classList.remove("active");
          showToast(data.message || "User deleted.", data.success ? "success" : "error");
          if (data.success) loadPage(1);
        })
        .catch(() => {
          overlay.classList.remove("active");
          showToast("Server error. Try again.", "error");
        });
    });

    overlay.querySelector(".cancel-btn").onclick = () => {
      overlay.classList.remove("active");
    };
  }

  function bindDeleteButtons() {
    document.querySelectorAll(".open-delete-modal").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = parseInt(btn.dataset.id);
        const role = btn.dataset.role;
        if (!isSpecialAdmin || id === currentAdminId) {
          showToast("You don’t have the authority to delete this.", 'error');
          return;
        }
        openDeleteModal(id, role);
      });
    });

    document.querySelectorAll(".disabled-delete-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        showToast("You don’t have permission to delete this user.", 'error');
      });
    });
  }

  loadPage(1);

});

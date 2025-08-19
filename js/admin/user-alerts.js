document.addEventListener("DOMContentLoaded", () => { 
  const config = window.adminRecordsConfig || {};

  // ⬇️ Permission flags (provided by PHP via hidden inputs)
  const isSpecialAdmin = document.getElementById('isSpecialAdmin')?.value === '1';
  const currentAdminId = parseInt(document.getElementById('currentAdminId')?.value || "0", 10);

  // ⬇️ Controls
  const search    = document.getElementById(config.searchId   || "reportSearch");
  const sortBy    = document.getElementById(config.sortId);
  const statusBy  = document.getElementById(config.statusId   || "statusFilter");
  const filterBy  = config.filterId    ? document.getElementById(config.filterId) : null; // optional (pending/flagged)
  const startDate = config.startDateId ? document.getElementById(config.startDateId) : null;
  const endDate   = config.endDateId   ? document.getElementById(config.endDateId)   : null;

  const tableBody = document.getElementById(config.tableBodyId || "records-table-body");
  const pagination= document.getElementById(config.paginationId || "pagination");
  const loadingOverlay = document.getElementById("loadingOverlay");

  // =========================
  // Helpers
  // =========================
  function showLoading(show) {
    if (loadingOverlay) loadingOverlay.style.display = show ? "flex" : "none";
  }

  // Always resolve the canonical USERS.ID for actions
  const userIdOf = (item) => {
    const v = Number(
      item?.user_id ?? item?.userId ?? item?.uid ?? item?.user ?? 0
    );
    return Number.isFinite(v) && v > 0 ? v : 0;
  };

  const storageKeyPrefix = config.apiUrl || "adminRecords";
  const STORAGE_KEYS = {
    search: `${storageKeyPrefix}_search`,
    sort: `${storageKeyPrefix}_sort`,
    status: `${storageKeyPrefix}_status`,
    filter: `${storageKeyPrefix}_filter`,
    start: `${storageKeyPrefix}_startDate`,
    end: `${storageKeyPrefix}_endDate`
  };

  // Load persisted filters
  if (search && localStorage.getItem(STORAGE_KEYS.search)) search.value = localStorage.getItem(STORAGE_KEYS.search);
  if (sortBy && localStorage.getItem(STORAGE_KEYS.sort))   sortBy.value = localStorage.getItem(STORAGE_KEYS.sort);
  if (filterBy && localStorage.getItem(STORAGE_KEYS.filter)) filterBy.value = localStorage.getItem(STORAGE_KEYS.filter);
  if (startDate && localStorage.getItem(STORAGE_KEYS.start)) startDate.value = localStorage.getItem(STORAGE_KEYS.start);
  if (endDate && localStorage.getItem(STORAGE_KEYS.end))     endDate.value   = localStorage.getItem(STORAGE_KEYS.end);

  // Default status = 'active' if none saved
  if (statusBy) {
    const savedStatus = localStorage.getItem(STORAGE_KEYS.status);
    statusBy.value = savedStatus ? savedStatus : "active";
  }

  function persistFilters() {
    if (search)    localStorage.setItem(STORAGE_KEYS.search, search.value.trim());
    if (sortBy)    localStorage.setItem(STORAGE_KEYS.sort, sortBy.value);
    if (statusBy)  localStorage.setItem(STORAGE_KEYS.status, statusBy.value);
    if (filterBy)  localStorage.setItem(STORAGE_KEYS.filter, filterBy.value);
    if (startDate) localStorage.setItem(STORAGE_KEYS.start, startDate.value);
    if (endDate)   localStorage.setItem(STORAGE_KEYS.end, endDate.value);
  }

  // =========================
  // Fetch + Render
  // =========================
  function fetchData(page = 1) {
    const params = new URLSearchParams();
    params.append("search", search?.value.trim() || "");
    if (sortBy)    params.append("sort",   sortBy.value);
    if (statusBy)  params.append("status", statusBy.value);
    if (filterBy)  params.append("filter", filterBy.value);
    if (startDate) params.append("start",  startDate.value);
    if (endDate)   params.append("end",    endDate.value);
    params.append("page", page);

    tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center;">⏳ Loading...</td></tr>`;

    fetch(`${config.apiUrl}?${params.toString()}`, { credentials: "include" })
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red;">❌ Failed to load data.</td></tr>`;
          return;
        }
        renderTable(data.data || [], page, data.perPage || 10);
        renderPagination(data.total || 0, data.perPage || 10, data.currentPage || page);
      })
      .catch(() => {
        tableBody.innerHTML = `<tr><td colspan="7" style="text-align:center; color:red;">⚠️ Network error.</td></tr>`;
      });
  }

  function renderTable(items, page, perPage) {
    const filterValue = filterBy ? filterBy.value : null;
    const tableHead = document.getElementById("tableHeadRow"); // optional header slot

    // Optional dynamic thead (if present)
    if (tableHead) {
      const widths = {
        pending: { num:"5%", name:"25%", email:"25%", registered:"15%", expires:"15%", action:"15%" },
        flagged: { num:"5%", name:"20%", email:"20%", usage:"15%", warned:"10%", flaggedAt:"15%", action:"15%"}
      };
      if (filterValue === "pending") {
        tableHead.innerHTML = `
          <tr>
            <th style="width:${widths.pending.num}">#</th>
            <th style="width:${widths.pending.name}">Name</th>
            <th style="width:${widths.pending.email}">Email</th>
            <th style="width:${widths.pending.registered}">Registered</th>
            <th style="width:${widths.pending.expires}">Expires At</th>
            <th style="width:${widths.pending.action}">Actions</th>
          </tr>`;
      } else if (filterValue === "flagged") {
        tableHead.innerHTML = `
          <tr>
            <th style="width:${widths.flagged.num}">#</th>
            <th style="width:${widths.flagged.name}">Name</th>
            <th style="width:${widths.flagged.email}">Email</th>
            <th style="width:${widths.flagged.usage}">Usage Duration</th>
            <th style="width:${widths.flagged.warned}">Warned</th>
            <th style="width:${widths.flagged.flaggedAt}">Flagged At</th>
            <th style="width:${widths.flagged.action}">Actions</th>
          </tr>`;
      } else {
        tableHead.innerHTML = ""; // let static thead stand on pages that have it
      }
    }

    if (!items.length) {
      const colSpan = filterValue === "flagged" ? 7 : 6;
      tableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">😐 No records found.</td></tr>`;
      return;
    }

    tableBody.innerHTML = items.map((item, i) => {
      const index = (page - 1) * perPage + i + 1;
      const createdAt = new Date(item.created_at);
      const createdDateStr = isNaN(createdAt) ? "-" : createdAt.toLocaleDateString();
      const createdTimeStr = isNaN(createdAt) ? "-" : createdAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).toLowerCase();

      // 🔐 Canonical user id (users.id). Disable actions if we don't have it.
      const uid = userIdOf(item);

      const mailBtn = `<a href="#" class="action-btn" data-id="${item.user_id || item.id}" data-email="${item.email}" data-action="send-email" title="Send Email">
                          <i class="fas fa-envelope" style="opacity: 0.7;"></i>
                      </a>`;

      const viewBtn = `<a href="/eyecheck/admin/patient/view.php?id=${item.user_id || item.id}" class="action-btn" title="View Details">
                          <i class="fas fa-eye" style="opacity: 0.7;"></i>
                       </a>`;
                       
      const printBtn = uid
        ? `<a href="#" class="action-btn print-patient" data-id="${uid}" title="Print Patient Report"><i class="fas fa-print" style="opacity:.8;"></i></a>`
        : `<button class="action-btn" disabled title="Missing user_id"><i class="fas fa-print" style="opacity:.3;"></i></button>`;

      let rowHtml = `
        <tr>
          <td>#${index}</td>
          <td>${item.full_name ?? "-"}</td>
          <td>${item.email ?? "-"}</td>
      `;

      if (filterValue === "pending") {
        const expiresDate = isNaN(createdAt) ? null : new Date(createdAt.getTime());
        if (expiresDate) expiresDate.setDate(expiresDate.getDate() + 2);
        const diffMs = expiresDate ? (expiresDate - new Date()) : 0;
        const oneDayMs = 24 * 60 * 60 * 1000;
        const expiresStyle = expiresDate && diffMs <= oneDayMs ? "color: red; font-weight: bold;" : "";
        rowHtml += `
          <td>${createdDateStr} <small style="font-weight:normal; font-size:0.8em; color:#666;">${createdTimeStr}</small></td>
          <td><span style="${expiresStyle}">${expiresDate ? expiresDate.toLocaleDateString() : "-"} <small style="font-size:0.8em; color:#666;">${expiresDate ? expiresDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).toLowerCase() : ""}</small></span></td>
          <td>${mailBtn}</td>
        `;
      } else if (filterValue === "flagged") {
        const now = new Date();
        const usageDurationDays = isNaN(createdAt) ? "-" : Math.floor((now - createdAt) / (1000 * 60 * 60 * 24));
        const warnedTimes = item.warnings_sent ?? 0;
        let lastWarned = 'N/A';
        if (item.flagged_at) {
          const flaggedDate = new Date(item.flagged_at);
          const diffDays = Math.floor((now - flaggedDate) / (1000 * 60 * 60 * 24));
          lastWarned = isNaN(flaggedDate) ? 'N/A' : (diffDays === 0 ? 'Today' : `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`);
        }
        rowHtml += `
          <td>${usageDurationDays} days</td>
          <td>${warnedTimes} times</td>
          <td>${lastWarned}</td>
          <td>${viewBtn}${mailBtn}</td>
        `;
      } else {
        // DEFAULT patients list (manage.php): Activate/Deactivate toggle + Delete rule
        const isActive = Number(item.is_active) === 1;
        const isSelf   = uid === currentAdminId;

        let toggleBtn = `
          <button class="action-btn" disabled title="No permission">
            <i class="fas fa-ban"></i>
          </button>`;

        if (isSpecialAdmin && !isSelf && uid) {
          const targetStatus = isActive ? 0 : 1;
          const label = isActive ? "Deactivate" : "Activate";
          const icon  = isActive ? "fa-ban" : "fa-rotate-left";
          toggleBtn = `
            <button class="action-btn toggle-status-btn"
                    data-id="${uid}"
                    data-target="${targetStatus}"
                    title="${label}" aria-label="${label}">
              <i class="fas ${icon}"></i>
            </button>`;
        }

        const deleteBtn = isActive || !uid
          ? `<button class="action-btn disabled-delete-btn" style="opacity:0.4; cursor:not-allowed;" title="${!uid ? 'Missing user_id' : 'You must deactivate first'}" aria-label="Delete"><i class="fas fa-trash-alt"></i></button>`
          : `<a class="action-btn open-delete-modal" data-id="${uid}" data-role="${item.role || ''}" data-action="delete" title="Delete" aria-label="Delete"><i class="fas fa-trash-alt" style="opacity:0.9;"></i></a>`;

        rowHtml += `
          <td>${createdDateStr} <small style="font-weight:normal; font-size:0.8em; color:#666;">${createdTimeStr}</small></td>
          <td>
            ${(config.showViewButton !== false ? viewBtn : '')}
            ${printBtn}
            ${toggleBtn}
            ${deleteBtn}
          </td>
        `;
      }

      rowHtml += `</tr>`;
      return rowHtml;
    }).join("");

    // Bind row actions
    setupDeleteButtons();
    setupMailButtons();
    setupPrintButtons();
    setupToggleButtons();

    document.querySelectorAll('.disabled-delete-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        showToast(btn.title || 'You must deactivate first.', 'error');
      });
    });
  }

  function renderPagination(total, perPage, currentPage) {
    pagination.innerHTML = "";
    const totalPages = Math.ceil(total / perPage);

    for (let i = 1; i <= totalPages; i++) {
      const btn = document.createElement("a");
      btn.href = "#";
      btn.textContent = i;
      if (i === currentPage) btn.classList.add("active");
      btn.addEventListener("click", e => {
        e.preventDefault();
        fetchData(i);
      });
      pagination.appendChild(btn);
    }

    if (totalPages > 3) {
      const goWrapper = document.createElement("div");
      goWrapper.style.marginTop = "10px";
      goWrapper.innerHTML = `
        <label for="goToPage">🔢 Go to page:</label>
        <input type="number" id="goToPage" min="1" max="${totalPages}" style="width: 60px; margin-left: 6px;" />
        <button id="goButton" style="margin-left: 6px;">Go</button>
      `;
      pagination.appendChild(goWrapper);

      document.getElementById("goButton").onclick = () => {
        const page = parseInt(document.getElementById("goToPage").value, 10);
        if (page >= 1 && page <= totalPages) fetchData(page);
      };
    }
  }

  // =========================
  // Actions: Delete / Print / Email / Toggle
  // =========================
  function setupDeleteButtons() {
    document.querySelectorAll('[data-action="delete"], .open-delete-modal').forEach(btn => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        const role = btn.dataset.role;
        if (!id) return showToast("Missing user_id", "error");
        openDeleteModal(id, role);
      });
    });
  }

  function setupPrintButtons() {
    document.querySelectorAll('.print-patient').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const id = btn.dataset.id;
        if (!id) return showToast("Missing user_id", "error");
        const url = `/eyecheck/admin/print-report.php?type=patient&id=${encodeURIComponent(id)}&months=3`;
        window.open(url, "_blank", "noopener,noreferrer");
      });
    });
  }

  function setupMailButtons() {
    document.querySelectorAll('[data-action="send-email"]').forEach(btn => {
      btn.addEventListener("click", e => {
        e.preventDefault();
        const id = btn.dataset.id;
        const email = btn.dataset.email;
        if (!id) return showToast("Missing user_id", "error");
        openSendEmailModal(id, email);
      });
    });
  }

  function setupToggleButtons() {
    document.querySelectorAll(".toggle-status-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = parseInt(btn.dataset.id, 10);
        const target = parseInt(btn.dataset.target, 10); // 0=deactivate, 1=activate
        if (!isSpecialAdmin || id === currentAdminId) {
          showToast("You can’t change your own status.", 'error');
          return;
        }
        openStatusModal(id, target);
      });
    });
  }

  // =========================
  // Modals (Email/Delete/Status)
  // =========================
  function openSendEmailModal(id, email) {
    const overlay   = document.querySelector(".delete-modal-overlay");
    const modal     = overlay.querySelector(".delete-modal");
    const title     = modal.querySelector("h2");
    const message   = modal.querySelector("p");
    const confirmBtn= modal.querySelector(".delete-confirm-btn");
    const iconEl    = modal.querySelector('.delete-icon i');

    iconEl.className = 'fas fa-envelope';
    iconEl.style.color = '#00c776';
    iconEl.style.fontSize = '40px';

    title.textContent = "Confirm Send Email";
    message.innerHTML = `Are you sure you want to send an email to <strong>${email}</strong>?`;
    confirmBtn.textContent = "Send Email";

    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = modal.querySelector(".delete-confirm-btn");
    newConfirmBtn.classList.remove("delete-btn");
    newConfirmBtn.style.backgroundColor = "#00c776";
    newConfirmBtn.style.color = "#fff";

    const fv = document.getElementById(config.filterId || "")?.value || "";
    const apiEndpoint = fv === "flagged"
      ? "/eyecheck/backend/admin/mail/send-patient-warning-email.php"
      : "/eyecheck/backend/admin/mail/send-reminder-email.php";

    newConfirmBtn.onclick = () => {
      newConfirmBtn.disabled = true;
      showLoading(true);

      fetch(apiEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ user_id: id }),
        credentials: "include"
      })
        .then(async (res) => {
          showLoading(false);
          try {
            const data = await res.json();
            showToast(data.message || "Email sent successfully!", data.success ? "success" : "error");
          } catch {
            const raw = await res.text();
            console.error("Non-JSON response:", raw);
            showToast("Unexpected server response.", "error");
          }
          overlay.classList.remove("active");
          newConfirmBtn.disabled = false;
        })
        .catch((err) => {
          console.error("Fetch error:", err);
          showLoading(false);
          showToast("Server error while sending email.", "error");
          overlay.classList.remove("active");
          newConfirmBtn.disabled = false;
        });
    };

    modal.querySelector(".cancel-btn").onclick = () => overlay.classList.remove("active");
    overlay.classList.add("active");
  }

  function openDeleteModal(id, role) {
    const overlay   = document.querySelector(".delete-modal-overlay");
    const modal     = overlay.querySelector(".delete-modal");
    const title     = modal.querySelector("h2");
    const message   = modal.querySelector("p");
    const confirmBtn= modal.querySelector(".delete-confirm-btn");
    const cancelBtn = modal.querySelector(".cancel-btn");
    const iconEl    = modal.querySelector('.delete-icon i');

    iconEl.className = 'fas fa-trash-alt';
    iconEl.style.color = '#ff3b3b';
    iconEl.style.fontSize = '40px';

    title.textContent = "Confirm Deletion";
    message.textContent = "Are you sure you want to delete this patient? This action cannot be undone.";
    confirmBtn.textContent = "Delete";

    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = modal.querySelector(".delete-confirm-btn");

    newConfirmBtn.classList.add("delete-btn");
    newConfirmBtn.style.backgroundColor = "";
    newConfirmBtn.style.color = "";

    newConfirmBtn.onclick = () => {
      showLoading(true);
      const formData = new URLSearchParams();
      formData.append("target_user_id", id);
      formData.append("role", role || "patient");

      fetch("/eyecheck/backend/shared/delete-handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: formData,
        credentials: "include"
      })
        .then(res => res.json())
        .then(data => {
          showLoading(false);
          showToast(data.message || "Deleted successfully", data.success ? "danger" : "error");
          overlay.classList.remove("active");
          fetchData(1);
        })
        .catch(() => {
          showLoading(false);
          showToast("Server error during deletion", "error");
          overlay.classList.remove("active");
        });
    };

    cancelBtn.onclick = () => overlay.classList.remove("active");
    overlay.classList.add("active");
  }

  // Reuse delete modal for Activate/Deactivate
  function openStatusModal(id, target) {
    const overlay   = document.querySelector(".delete-modal-overlay");
    const modal     = overlay.querySelector(".delete-modal");
    const iconEl    = modal.querySelector(".delete-icon i");
    const titleEl   = modal.querySelector("h2");
    const textEl    = modal.querySelector("p");
    const confirmBtn= modal.querySelector(".delete-confirm-btn");
    const cancelBtn = modal.querySelector(".cancel-btn");

    if (!overlay.dataset.defaultTitle) {
      overlay.dataset.defaultTitle  = titleEl.textContent;
      overlay.dataset.defaultText   = textEl.textContent;
      overlay.dataset.defaultIcon   = iconEl.className;
      overlay.dataset.defaultConfirm= confirmBtn.textContent;
    }

    const isDeactivation = target === 0;

    titleEl.textContent = isDeactivation ? "Confirm Deactivation" : "Confirm Activation";
    textEl.textContent  = isDeactivation
      ? "This user will be prevented from signing in and performing any actions."
      : "This user will regain access and can sign in again.";
    iconEl.className    = isDeactivation ? "fas fa-ban" : "fas fa-rotate-left";
    confirmBtn.textContent = isDeactivation ? "Deactivate" : "Activate";

    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = modal.querySelector(".delete-confirm-btn");

    newConfirmBtn.addEventListener("click", () => {
      showLoading(true);
      const bodyData = new URLSearchParams({ target_user_id: String(id), status: String(target) });

      fetch("/eyecheck/backend/admin/toggle-status.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: bodyData,
        credentials: "include"
      })
        .then(r => r.json())
        .then(data => {
          showLoading(false);
          overlay.classList.remove("active");
          restoreDeleteModal(overlay);
          showToast(data.message || "Updated.", data.success ? "success" : "error");
          if (data.success) fetchData(1);
        })
        .catch(() => {
          showLoading(false);
          overlay.classList.remove("active");
          restoreDeleteModal(overlay);
          showToast("Server error. Try again.", "error");
        });
    });

    cancelBtn.onclick = () => {
      overlay.classList.remove("active");
      restoreDeleteModal(overlay);
    };

    overlay.classList.add("active");
  }

  function restoreDeleteModal(overlay) {
    const modal     = overlay.querySelector(".delete-modal");
    const iconEl    = modal.querySelector(".delete-icon i");
    const titleEl   = modal.querySelector("h2");
    const textEl    = modal.querySelector("p");
    const confirmBtn= modal.querySelector(".delete-confirm-btn");

    if (overlay.dataset.defaultTitle) {
      titleEl.textContent   = overlay.dataset.defaultTitle;
      textEl.textContent    = overlay.dataset.defaultText;
      iconEl.className      = overlay.dataset.defaultIcon;
      confirmBtn.textContent= overlay.dataset.defaultConfirm;
    }
  }

  // =========================
  // Toast
  // =========================
  function showToast(message = "Done!", type = "success") {
    const toast = document.getElementById("toast");
    const toastMsg = document.getElementById("toastMessage");
    if (!toast || !toastMsg) return;

    toastMsg.textContent = message;
    toast.classList.remove("danger", "success", "error", "show");

    const icon = toast.querySelector("i");
    if (type === "success") {
      toast.classList.add("success");
      if (icon) icon.className = "fas fa-check-circle";
    } else if (type === "danger") {
      toast.classList.add("danger");
      if (icon) icon.className = "fas fa-trash-alt";
    } else {
      toast.classList.add("error");
      if (icon) icon.className = "fas fa-exclamation-circle";
    }

    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 3000);
  }

  // =========================
  // Events
  // =========================
  if (search) {
    search.addEventListener("keyup", e => {
      if (e.key === "Enter") {
        persistFilters();
        fetchData(1);
      }
    });
  }

  [search, sortBy, filterBy, startDate, endDate, statusBy].forEach(el => {
    if (el) el.addEventListener("change", () => {
      persistFilters();
      fetchData(1);
    });
  });

  // Initial load
  fetchData(1);
});

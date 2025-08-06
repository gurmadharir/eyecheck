document.addEventListener("DOMContentLoaded", () => {
  const config = window.adminRecordsConfig || {};

  const search = document.getElementById(config.searchId || "reportSearch");
  const sortBy = document.getElementById(config.sortId);
  const filterBy = config.filterId ? document.getElementById(config.filterId) : null;
  const startDate = config.startDateId ? document.getElementById(config.startDateId) : null;
  const endDate = config.endDateId ? document.getElementById(config.endDateId) : null;
  const tableBody = document.getElementById(config.tableBodyId || "records-table-body");
  const pagination = document.getElementById(config.paginationId || "pagination");
  const loadingOverlay = document.getElementById("loadingOverlay");

  function showLoading(show) {
    if (loadingOverlay) loadingOverlay.style.display = show ? "flex" : "none";
  }

  // LOCAL STORE - FILTERS e.tc
  const storageKeyPrefix = config.apiUrl || "adminRecords";
  const STORAGE_KEYS = {
    search: `${storageKeyPrefix}_search`,
    sort: `${storageKeyPrefix}_sort`,
    filter: `${storageKeyPrefix}_filter`,
    start: `${storageKeyPrefix}_startDate`,
    end: `${storageKeyPrefix}_endDate`
  };

  // Load from localStorage
  if (search && localStorage.getItem(STORAGE_KEYS.search)) {
    search.value = localStorage.getItem(STORAGE_KEYS.search);
  }
  if (sortBy && localStorage.getItem(STORAGE_KEYS.sort)) {
    sortBy.value = localStorage.getItem(STORAGE_KEYS.sort);
  }
  if (filterBy && localStorage.getItem(STORAGE_KEYS.filter)) {
    filterBy.value = localStorage.getItem(STORAGE_KEYS.filter);
  }
  if (startDate && localStorage.getItem(STORAGE_KEYS.start)) {
    startDate.value = localStorage.getItem(STORAGE_KEYS.start);
  }
  if (endDate && localStorage.getItem(STORAGE_KEYS.end)) {
    endDate.value = localStorage.getItem(STORAGE_KEYS.end);
  }


  function fetchData(page = 1) {
    const params = new URLSearchParams();
    params.append("search", search.value.trim());
    if (sortBy) params.append("sort", sortBy.value);
    if (filterBy) params.append("filter", filterBy.value);
    if (startDate) params.append("start", startDate.value);
    if (endDate) params.append("end", endDate.value);
    params.append("page", page);

    tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;">⏳ Loading...</td></tr>`;

    fetch(`${config.apiUrl}?${params.toString()}`)
      .then(res => res.json())
      .then(data => {
        if (!data.success) {
          tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">❌ Failed to load data.</td></tr>`;
          return;
        }
        renderTable(data.data, page, data.perPage);
        renderPagination(data.total, data.perPage, data.currentPage);
      })
      .catch(() => {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">⚠️ Network error.</td></tr>`;
      });
  }

  function persistFilters() {
    if (search) localStorage.setItem(STORAGE_KEYS.search, search.value.trim());
    if (sortBy) localStorage.setItem(STORAGE_KEYS.sort, sortBy.value);
    if (filterBy) localStorage.setItem(STORAGE_KEYS.filter, filterBy.value);
    if (startDate) localStorage.setItem(STORAGE_KEYS.start, startDate.value);
    if (endDate) localStorage.setItem(STORAGE_KEYS.end, endDate.value);
  }


    
  function renderTable(items, page, perPage) {
    const filterValue = filterBy ? filterBy.value : null;
    const tableHead = document.getElementById("tableHeadRow");

    // 🔁 Render <thead> dynamically
    if (filterValue === "pending") {
      tableHead.innerHTML = `
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Registered</th>
          <th>Expires At</th>
          <th>Actions</th>
        </tr>
      `;
    } else if (filterValue === "flagged") {
      tableHead.innerHTML = `
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Email</th>
          <th>Usage Duration</th>
          <th>Warned Times</th>
          <th>Flagged At</th>
          <th>Actions</th>
        </tr>
      `;
    }

    if (!items.length) {
      const colSpan = filterValue === "flagged" ? 7 : 6;
      tableBody.innerHTML = `<tr><td colspan="${colSpan}" style="text-align:center;">😐 No records found.</td></tr>`;
      return;
    }

    tableBody.innerHTML = items.map((item, i) => {
      const index = (page - 1) * perPage + i + 1;
      const createdAt = new Date(item.created_at);
      const createdDateStr = createdAt.toLocaleDateString();
      const createdTimeStr = createdAt.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).toLowerCase();


      const mailBtn = `<a href="#" class="action-btn" data-id="${item.id}" data-email="${item.email}" data-action="send-email" title="Send Email">
                          <i class="fas fa-envelope" style="opacity: 0.7;"></i>
                      </a>`;

      const viewBtn = `<a href="/eyecheck/admin/patients/view.php?id=${item.id}" class="action-btn" title="View Details">
                          <i class="fas fa-eye" style="opacity: 0.7;"></i>
                       </a>`;

      const deleteBtn = filterValue !== "pending"
        ? `<a class="action-btn" data-id="${item.user_id}" data-role="${item.role || ''}" data-action="delete" title="Delete">
            <i class="fas fa-trash-alt" style="opacity: 0.7;"></i>
          </a>` 
        : '';

      let rowHtml = `
        <tr>
          <td>#${index}</td>
          <td>${item.full_name}</td>
          <td>${item.email}</td>
      `;

      if (filterValue === "pending") {
        const expiresDate = new Date(createdAt);
        expiresDate.setDate(expiresDate.getDate() + 2);
        const now = new Date();
        const diffMs = expiresDate - now;
        const oneDayMs = 24 * 60 * 60 * 1000;
        const expiresStyle = diffMs <= oneDayMs ? "color: red; font-weight: bold;" : "";

        rowHtml += `
          <td>${createdDateStr} <small style="font-weight:normal; font-size:0.8em; color:#666;">${createdTimeStr}</small></td>
          <td><span style="${expiresStyle}">${expiresDate.toLocaleDateString()} <small style="font-size:0.8em; color:#666;">${expiresDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }).toLowerCase()}</small></span></td>
          <td>${mailBtn}${deleteBtn}</td>
        `;
      } else if (filterValue === "flagged") {
        const now = new Date();
        const usageDurationDays = Math.floor((now - createdAt) / (1000 * 60 * 60 * 24));
        const warnedTimes = item.warnings_sent ?? 0;
        let lastWarned = 'N/A';
        if (item.flagged_at) {
          const flaggedDate = new Date(item.flagged_at);
          const now = new Date();
          const diffDays = Math.floor((now - flaggedDate) / (1000 * 60 * 60 * 24));
          lastWarned = diffDays === 0 ? 'Today' : `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
        }


        rowHtml += `
          <td>${usageDurationDays} days</td>
          <td>${warnedTimes}</td>
          <td>${lastWarned}</td>
          <td>${viewBtn}${mailBtn}${deleteBtn}</td>
        `;
      }

      rowHtml += `</tr>`;
      return rowHtml;
    }).join("");

    setupDeleteButtons();
    setupMailButtons();
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
        const page = parseInt(document.getElementById("goToPage").value);
        if (page >= 1 && page <= totalPages) fetchData(page);
      };
    }
  }

  function setupDeleteButtons() {
    document.querySelectorAll('[data-action="delete"]').forEach(btn => {
      btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        const role = btn.dataset.role;
        openDeleteModal(id, role);
      });
    });
  }

  function setupMailButtons() {
    document.querySelectorAll('[data-action="send-email"]').forEach(btn => {
      btn.addEventListener("click", e => {
        e.preventDefault();
        const id = btn.dataset.id;
        const email = btn.dataset.email;

        openSendEmailModal(id, email);
      });
    });
  }

  function openSendEmailModal(id, email) {
    const overlay = document.querySelector(".delete-modal-overlay");
    const modal = overlay.querySelector(".delete-modal");
    const title = modal.querySelector("h2");
    const message = modal.querySelector("p");
    const confirmBtn = modal.querySelector(".delete-confirm-btn");
    const cancelBtn = modal.querySelector(".cancel-btn");
    const iconEl = modal.querySelector('.delete-icon i');

    const currentPage = document.getElementById("pageTitle")?.textContent?.toLowerCase();
    const isPatientPage = currentPage?.includes("patients");

    // Set to envelope icon for mail
    iconEl.className = 'fas fa-envelope';
    iconEl.style.color = '#00c776';
    iconEl.style.fontSize = '40px';

    title.textContent = "Confirm Send Email";
    message.innerHTML = isPatientPage
      ? `Are you sure to send a healthcare warning email to <strong>${email}</strong>?`
      : `Are you sure to send a reminder email to <strong>${email}</strong>?`;

    confirmBtn.textContent = "Send Email";

    // Clone and rebind
    confirmBtn.replaceWith(confirmBtn.cloneNode(true));
    const newConfirmBtn = modal.querySelector(".delete-confirm-btn");

    // ✅ Set green style after defining it
    newConfirmBtn.classList.remove("delete-btn");
    newConfirmBtn.style.backgroundColor = "#00c776"; 
    newConfirmBtn.style.color = "#fff";

    newConfirmBtn.onclick = () => {
      newConfirmBtn.disabled = true;
      showLoading(true);

      const apiEndpoint = isPatientPage
        ? "/eyecheck/backend/admin/mail/send-patient-warning-email.php"
        : "/eyecheck/backend/admin/mail/send-reminder-email.php";

    fetch(apiEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({ user_id: id }),
        credentials: "include"
      })
      .then(async (res) => {
      showLoading(false);

      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch (err) {
        console.error("❌ Invalid JSON:", text);
        showToast("Unexpected server response.", "error");
        return;
      }

      showToast(data.message || "Email sent successfully!", data.success ? "success" : "error");
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

    cancelBtn.onclick = () => {
      overlay.classList.remove("active");
    };

    overlay.classList.add("active");
  }



function openDeleteModal(id, role) {
  const overlay = document.querySelector(".delete-modal-overlay");
  const modal = overlay.querySelector(".delete-modal");
  const title = modal.querySelector("h2");
  const message = modal.querySelector("p");
  const confirmBtn = modal.querySelector(".delete-confirm-btn");
  const cancelBtn = modal.querySelector(".cancel-btn");
  const iconEl = modal.querySelector('.delete-icon i');

  // Reset icon to trash for delete action
  iconEl.className = 'fas fa-trash-alt';
  iconEl.style.color = '#ff3b3b';
  iconEl.style.fontSize = '40px';



  title.textContent = "Confirm Deletion";
  message.textContent = "Are you sure you want to delete this patient? This action cannot be undone.";
  confirmBtn.textContent = "Delete";

  confirmBtn.replaceWith(confirmBtn.cloneNode(true));
  const newConfirmBtn = modal.querySelector(".delete-confirm-btn");

  // Red confirm btn
  newConfirmBtn.classList.add("delete-btn");
  newConfirmBtn.style.backgroundColor = "";
  newConfirmBtn.style.color = "";


  newConfirmBtn.onclick = () => {
    showLoading(true);
    const formData = new URLSearchParams();
    formData.append("target_user_id", id);
    formData.append("role", "admin");

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

  cancelBtn.onclick = () => {
    overlay.classList.remove("active");
  };

  overlay.classList.add("active");
}

  function showToast(message = "Done!", type = "success") {
    const toast = document.getElementById("toast");
    const toastMsg = document.getElementById("toastMessage");

    toastMsg.textContent = message;

    // Reset toast classes and icon
    toast.classList.remove("danger", "success", "error", "show");

    const icon = toast.querySelector("i");
    if (type === "success") {
      toast.classList.add("success");
      icon.className = "fas fa-check-circle";
    } else if (type === "danger") {
      toast.classList.add("danger");
      icon.className = "fas fa-trash-alt";
    } else {
      toast.classList.add("error");
      icon.className = "fas fa-exclamation-circle";
    }

    toast.classList.add("show");
    setTimeout(() => toast.classList.remove("show"), 3000);
  }


  function showLoading(show) {
    const overlay = document.getElementById("loadingOverlay");
    if (overlay) overlay.style.display = show ? "flex" : "none";
  }

  if (search) {
    search.addEventListener("keyup", e => {
      if (e.key === "Enter") {
        persistFilters();
        fetchData(1);
      }
    });
  }

  [search, sortBy, filterBy, startDate, endDate].forEach(el => {
    if (el) el.addEventListener("change", () => {
      persistFilters();
      fetchData(1);
    });
  });


  fetchData(1);
});

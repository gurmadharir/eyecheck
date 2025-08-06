document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("records-table-body");
  const paginationContainer = document.getElementById("pagination");
  const result = document.getElementById("resultFilter");
  const sort = document.getElementById("dateFilter");
  const startDate = document.getElementById("startDate");
  const endDate = document.getElementById("endDate");

  initFiltersFromURL();
  loadPage(getURLPage() || 1);

  [result, sort, startDate, endDate].forEach(input => {
    input.addEventListener("input", () => loadPage(1));
    input.addEventListener("change", () => loadPage(1));
  });

  function loadPage(page = 1) {
    const selectedResult = result.value === 'Conjunctivitis' ? 'Positive' : result.value;

    const params = new URLSearchParams({
      result: selectedResult,
      sort: sort.value,
      start: startDate.value,
      end: endDate.value,
      page: page
    });

    updateURL(params);

    fetch("/eyecheck/backend/patient/get-patient-uploads.php?" + params)
      .then(res => res.json())
      .then(response => {
        if (!response.success) {
          tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Error loading data.</td></tr>`;
          return;
        }
        renderTable(response.data, response.currentPage, response.perPage);
        renderPagination(response.total, response.perPage, response.currentPage);
      })
      .catch(() => {
        tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:red;">Failed to fetch data.</td></tr>`;
      });
  }

  function renderTable(data, currentPage, perPage) {
    if (data.length === 0) {
      tableBody.innerHTML = `<tr><td colspan="5" style="text-align:center;">🧐 No uploads found.</td></tr>`;
      return;
    }

    tableBody.innerHTML = data.map((record, index) => {
      const uploaded = new Date(record.created_at).toLocaleDateString();
      return `
        <tr>
          <td>#${(currentPage - 1) * perPage + index + 1}</td>
          <td><img src="../${record.image_path}" style="width:80px;height:50px;object-fit:cover;border-radius:6px;" /></td>
          <td class="${record.diagnosis_result.toLowerCase() === 'positive' ? 'positive' : 'negative'}">
            ${record.diagnosis_result === 'Positive' ? 'Conjunctivitis' : record.diagnosis_result}
          </td>
          <td>${uploaded}</td>
          <td style="white-space:nowrap;">
            <a class="action-btn" style="margin-right: 18px;" href="view-upload.php?id=${record.id}" title="View"><i class="fas fa-eye"></i></a>
            <button class="action-btn delete-btn-trigger" title="Delete" data-id="${record.id}" data-type="upload">
              <i class="fas fa-trash-alt"></i>
            </button>
          </td>
        </tr>`;
    }).join("");

    attachDeleteListeners();
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

   function attachDeleteListeners() {
    document.querySelectorAll(".delete-btn-trigger").forEach(btn => {
      btn.addEventListener("click", () => {
        const id = btn.getAttribute("data-id");
        const type = btn.getAttribute("data-type");
        const row = btn.closest("tr");

        const modal = document.querySelector(".delete-modal-overlay");
        modal.classList.add("active");

        const confirmBtn = modal.querySelector(".delete-btn");
        const cancelBtn = modal.querySelector(".cancel-btn");

        const newConfirm = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirm, confirmBtn);

        cancelBtn.onclick = () => modal.classList.remove("active");

        newConfirm.onclick = () => {
          const deleteUrl = "/eyecheck/backend/shared/delete-handler.php";
          const bodyData = new URLSearchParams({ target_upload_id: id, role: 'patient' });

          fetch(deleteUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: bodyData,
            credentials: "include"
          })
          .then(async res => {
            try {
              const data = await res.json();
              modal.classList.remove("active");
              if (data.success) {
                showToast("Upload deleted successfully!", "success");
                row.remove();
              } else {
                showToast(data.message || "Delete failed.", "error");
              }
            } catch {
              modal.classList.remove("active");
              showToast("Invalid server response.", "error");
            }
          })
          .catch(() => {
            modal.classList.remove("active");
            showToast("Server error. Please try again.", "error");
          });
        };
      });
    });
  }

  function updateURL(params) {
    const newUrl = window.location.pathname + "?" + params.toString();
    history.replaceState(null, "", newUrl);
  }

  function getURLPage() {
    const url = new URLSearchParams(window.location.search);
    return parseInt(url.get("page"));
  }

  function initFiltersFromURL() {
    const url = new URLSearchParams(window.location.search);
    result.value = url.get("result") || "all";
    sort.value = url.get("sort") || "latest";
    startDate.value = url.get("start") || "";
    endDate.value = url.get("end") || "";
  }
});

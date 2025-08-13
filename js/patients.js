
document.addEventListener("DOMContentLoaded", () => {
  const tableBody = document.getElementById("records-table-body");
  const paginationContainer = document.getElementById("pagination");
  const search = document.getElementById("reportSearch");
  const gender = document.getElementById("genderFilter");
  const result = document.getElementById("resultFilter");
  const region = document.getElementById("regionFilter");
  const age = document.getElementById("ageFilter");
  const sort = document.getElementById("dateFilter");
  const startDate = document.getElementById("startDate");
  const endDate = document.getElementById("endDate");

  initFiltersFromURL();
  let currentPage = getURLPage() || 1;
  loadPage(currentPage);

  [gender, result, region, age, sort, startDate, endDate].forEach(input => {
    input.addEventListener("change", () => loadPage(1));
  });

  let searchTimeout;
  search.addEventListener("input", () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      loadPage(1);
    }, 500);
  });

  function loadPage(page = 1) {
    const params = new URLSearchParams({
      search: search.value,
      gender: gender.value,
      result: result.value,
      region: region.value,
      age: age.value,
      sort: sort.value,
      start: startDate.value,
      end: endDate.value,
      page
    });

    updateURL(params);

    fetch("/eyecheck/backend/patients/get.php?" + params)
      .then(res => res.json())
      .then(response => {
        if (!response.success) {
          tableBody.innerHTML = "<tr><td colspan='10' style='text-align:center; padding:1em;'>🚫 Error loading data.</td></tr>";
          return;
        }
        renderTable(response.data, response.currentPage, response.perPage);
        renderPagination(response.total, response.perPage, response.currentPage);
      })
      .catch(() => {
        tableBody.innerHTML = "<tr><td colspan='10' style='text-align:center; padding:1em;'>⚠️ Fetch failed.</td></tr>";
      });
  }

  
  function renderTable(data, currentPage, perPage) {
    if (!Array.isArray(data) || data.length === 0) {
      tableBody.innerHTML = "<tr><td colspan='10' style='text-align:center; padding:1em;'>🧐 No patients found!</td></tr>";
      return;
    }

    tableBody.innerHTML = data.map((record, index) => {
      // --- age (safer) ---
      const dob = record.dob ? new Date(record.dob) : null;
      const ageYears = dob ? (new Date(Date.now() - dob.getTime()).getUTCFullYear() - 1970) : '';

      // --- normalize result (supports Conjunctivitis/NonConjunctivitis and Positive/Negative) ---
      const raw = String(record.diagnosis_result || '').trim();
      const low = raw.toLowerCase();
      let displayResult = raw.replace(/([a-z])([A-Z])/g, '$1 $2'); // fallback prettifier
      let resultClass = 'neutral';

      if (low === 'conjunctivitis' || low === 'positive') {
        displayResult = 'Conjunctivitis';
        resultClass = 'positive';
      } else if (low === 'nonconjunctivitis' || low === 'negative') {
        displayResult = 'Non Conjunctivitis';
        resultClass = 'negative';
      }

      // --- color style (red for Conjunctivitis, green otherwise) ---
      const resultStyle = (displayResult.toLowerCase() === 'conjunctivitis')
        ? 'color:#e74c3c;font-weight:bold;'
        : 'color:#27ae60;font-weight:bold;';

      const rowNum = (currentPage - 1) * perPage + index + 1;
      const imgSrc = record.image_path ? `/eyecheck/${record.image_path}` : '';

      return `
        <tr>
          <td>#${rowNum}</td>
          <td>${record.name || ''}</td>
          <td><img src="${imgSrc}" style="width:80px;height:50px;object-fit:cover;border-radius:6px;" /></td>
          <td>${record.contact || ''}</td>
          <td>${record.town || ''}</td>
          <td>${record.region || ''}</td>
          <td>${record.gender || ''}</td>
          <td>${dob ? dob.getFullYear() : ''} ${ageYears !== '' ? `<small>(${ageYears} yrs)</small>` : ''}</td>
          <td class="${resultClass}" style="${resultStyle}">${displayResult}</td>
          <td style="white-space:nowrap;">
            <button class="action-btn edit-btn" title="Edit" data-id="${record.id}"><i class="fas fa-pen-to-square"></i></button>
            <button class="action-btn view-btn" data-id="${record.upload_id}" title="View"><i class="fas fa-eye"></i></button>
            <button class="action-btn delete-btn-trigger" title="Delete" data-id="${record.id}" data-type="patient"><i class="fas fa-trash-alt"></i></button>
          </td>
        </tr>`;
    }).join("");

    attachDeleteListeners();
    attachOtherActionListeners();
  }

  function populateRegionFilter() {
    fetch("/eyecheck/backend/patients/get-healthcare-regions.php")
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const regionFilter = document.getElementById("regionFilter");
          regionFilter.innerHTML = `<option value="all">All</option>`;
          data.regions.forEach(region => {
            const opt = document.createElement("option");
            opt.value = region;
            opt.textContent = region;
            regionFilter.appendChild(opt);
          });

          // Restore previous selection from URL if available
          const url = new URLSearchParams(window.location.search);
          regionFilter.value = url.get("region") || "all";
        }
      })
      .catch(() => console.error("Failed to load region list"));
  }

  populateRegionFilter();

  function renderPagination(total, perPage, currentPage) {
    paginationContainer.innerHTML = "";
    const totalPages = Math.ceil(total / perPage);
    if (totalPages <= 1) return;

    for (let i = 1; i <= totalPages; i++) {
      const a = document.createElement("a");
      a.textContent = i;
      a.href = "#";
      if (i === currentPage) {
        a.classList.add("active");
        a.style.background = "green";
        a.style.borderRadius = "50%";
        a.style.color = "white";
        a.style.padding = "0.3em 0.7em";
      }
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
      const type = btn.getAttribute("data-type"); // "patient"
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
        const bodyData = new URLSearchParams({ target_patient_id: id, role: 'healthcare'});

        fetch(deleteUrl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: bodyData,
          credentials: "include"
        })
        .then(res => res.json())
        .then(data => {
          modal.classList.remove("active");
          if (data.success) {
            showToast(`${type.charAt(0).toUpperCase() + type.slice(1)} deleted successfully!`, "success");
            row.remove();
          } else {
            showToast(data.message || "Delete failed.", "error");
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


  function attachOtherActionListeners() {
    document.querySelectorAll(".view-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        window.location.href = `./patients/view-patient.php?id=${btn.dataset.id}`;
      });
    });
    document.querySelectorAll(".edit-btn").forEach(btn => {
      btn.addEventListener("click", () => {
        window.location.href = `./patients/edit-patient.php?id=${btn.dataset.id}`;
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
    search.value = url.get("search") || "";
    gender.value = url.get("gender") || "all";
    result.value = url.get("result") || "all";
    region.value = url.get("region") || "all";
    age.value = url.get("age") || "all";
    sort.value = url.get("sort") || "latest";
    startDate.value = url.get("start") || "";
    endDate.value = url.get("end") || "";
  }
});

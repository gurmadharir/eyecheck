document.addEventListener("DOMContentLoaded", function () {
  const role = typeof userRole !== "undefined" ? userRole : "guest";

  function renderNoData(wrapperId, message) {
    const wrapper = document.getElementById(wrapperId);
    if (wrapper) {
      wrapper.innerHTML = `
        <div style='text-align:center; padding: 20px;'>
          <img src='../assets/images/no-data.png' alt='No data' style='max-width: 220px; opacity: 0.6;' />
          <p style='color: #888; margin-top: 12px;'>${message}</p>
        </div>
      `;
    }
  }

  fetch("/eyecheck/backend/dashboard/stats.php", {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ role })
  })
    .then(res => res.json())
    .then(data => {
      if (data.error) return console.error("Stats error:", data.error);

      const set = (id, val) => {
        const el = document.getElementById(id);
        if (el) el.textContent = val ?? "-";
      };

      const s = data.summary || {};
      const a = data.adminCards || {};
      const trendData = role === "patient" ? data.uploadTrend : data.trend;

      // === Trend Chart ===
      if (!trendData?.length) renderNoData("trendWrapper", "No trend data.");
      else {
        new Chart("trendChart", {
          type: "line",
          data: {
            labels: trendData.map(d => d.date),
            datasets: role === "patient"
              ? [{
                  label: "Uploads",
                  data: trendData.map(d => d.total),
                  borderColor: "#3949ab",
                  fill: true,
                  tension: 0.3
                }]
              : [
                  {
                    label: "Conjunctivitis",
                    data: trendData.map(d => d.positive),
                    borderColor: "#e53935",
                    fill: true,
                    tension: 0.3
                  },
                  {
                    label: "Negative",
                    data: trendData.map(d => d.negative),
                    borderColor: "#43a047",
                    fill: true,
                    tension: 0.3
                  }
                ]
          },
          options: {
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
          }
        });
      }

      // === Shared Detection Chart ===
      const [positive, negative] = data.detectionResults || [0, 0];
      if (positive + negative === 0) renderNoData("casesWrapper", "No detection data.");
      else new Chart("casesChart", {
        type: "bar",
        data: {
          labels: ["Conjunctivitis", "Non Conjunctivitis"],
          datasets: [{
            data: [positive, negative],
            backgroundColor: ["#e53935", "#43a047"]
          }]
        },
        options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
      });

      // === Admin Summary ===
      if (role === "admin") {
        set("statTotalPatients", a.total_patients);
        set("statHealthcareUsers", a.total_healthcare);
        set("statRegions", a.regions_count);

        const acc = a.model_accuracy ?? 0;
        const accEl = document.getElementById("statModelAccuracy");

        if (accEl) {
          accEl.textContent = `${acc}%`;

          // Color logic:
          // Green if >= 90%
          // Yellow if 75–89%
          // Red if < 75%
          accEl.style.color =
            acc >= 90 ? "#2e7d32" :        
            acc >= 75 ? "#fbc02d" :        
                        "#e53935";         
        }

        const latest = a.last_upload ?? "-";
        const latestEl = document.getElementById("statLatest");
        if (latestEl && latest.includes(" ")) {
          const [d, t] = latest.split(" ");
          latestEl.innerHTML = `<span>${d}</span><br><span style="font-size:10px;">${t}</span>`;
        }

        const total = positive + negative;
        const detectRateEl = document.getElementById("statDetectRate");

        if (detectRateEl) {
          if (total === 0) {
            detectRateEl.textContent = "0%";
            detectRateEl.style.color = "#999"; // neutral gray
          } else {
            const ratio = ((positive / total) * 100).toFixed(1);
            detectRateEl.textContent = `${ratio}%`;
            detectRateEl.style.color = ratio > 50 ? "#e74c3c" : "#2ecc71"; // red or green
          }
        }

        set("statRepeatedPositives", a.repeated_positive_count);
        const card = document.getElementById("cardRepeatedPositives");
        if (card && a.repeated_positive_ids?.length) {
          card.addEventListener("click", () => console.log("Repeated IDs:", a.repeated_positive_ids));
        }

        const worker = document.getElementById("mostActiveWorker");
        if (worker && data.mostActiveHealthcare) {
          worker.textContent = data.mostActiveHealthcare;
        }

        // Region User Chart
        if (data.regionUsers?.length) {
          new Chart("regionUsersChart", {
            type: "bar",
            data: {
              labels: data.regionUsers.map(r => r.region),
              datasets: [
                { label: "Patients", data: data.regionUsers.map(r => r.patients), backgroundColor: "#3949ab" },
                { label: "Healthcare", data: data.regionUsers.map(r => r.healthcare), backgroundColor: "#fbc02d" }
              ]
            },
            options: {
              responsive: true,
              plugins: { legend: { position: "bottom" } },
              scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
          });
        }

        // Worker Summary
        if (data.workerSummary?.length) {
          new Chart("workerSummaryChart", {
            type: "bar",
            data: {
              labels: data.workerSummary.map(w => w.name),
              datasets: [
                { label: "Conjunctivitis", data: data.workerSummary.map(w => w.positive), backgroundColor: "#e53935" },
                { label: "Negative", data: data.workerSummary.map(w => w.negative), backgroundColor: "#43a047" }
              ]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
          });
        }

        // Region Shared Trend
        if (data.regionSharedTrend?.length) {
          const labels = data.regionSharedTrend.map(d => d.date);
          const keys = Object.keys(data.regionSharedTrend[0]).filter(k => k !== "date");

          const datasets = keys.map(k => ({
            label: k,
            data: data.regionSharedTrend.map(x => x[k]),
            tension: 0.3,
            fill: false
          }));

          new Chart("regionSharedTrendChart", {
            type: "line",
            data: { labels, datasets },
            options: {
              responsive: true,
              interaction: { mode: "index", intersect: false },
              scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
          });
        } else renderNoData("regionSharedWrapper", "No region trend data.");

        // Monthly Upload Chart
        if (data.uploadsPerMonth?.length) {
          const labels = data.uploadsPerMonth.map(d => d.month);
          const healthcareData = data.uploadsPerMonth.map(d => d.healthcare_uploads || 0);
          const patientData = data.uploadsPerMonth.map(d => d.patient_uploads || 0);

          new Chart("monthlyUploadChart", {
            type: "line",
            data: {
              labels,
              datasets: [
                {
                  label: "Healthcare uploads",
                  data: healthcareData,
                  borderColor: "#1e88e5",
                  backgroundColor: "rgba(30, 136, 229, 0.1)",
                  fill: true,
                  tension: 0.3
                },
                {
                  label: "Patient uploads",
                  data: patientData,
                  borderColor: "#f9a825",
                  backgroundColor: "rgba(249, 168, 37, 0.1)",
                  fill: true,
                  tension: 0.3
                }
              ]
            },
            options: {
              responsive: true,
              plugins: {
                legend: { position: "bottom" },
                title: { display: true, text: "Monthly upload trend by role" }
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: { precision: 0 },
                  title: { display: true, text: "Uploads" }
                },
                x: { title: { display: true, text: "Month" } }
              }
            }
          });
        } else renderNoData("monthlyUploadWrapper", "No monthly data.");
      }

      // === Healthcare Summary ===
      if (role === "healthcare") {
        set("statTotal", s.total);
        set("statRegions", s.regions);
        set("statAverageAge", s.average_age);

        const el = document.getElementById("statLatest");
        if (el && s.latest?.includes(" ")) {
          const [d, t] = s.latest.split(" ");
          el.innerHTML = `<span>${d}</span><br><span style="font-size:10px;">${t}</span>`;
        }

        // Weekly Upload Chart (Healthcare Only)
        if (data.weeklyUploads?.length) {
          const ctx = document.getElementById("weeklyUploadChart")?.getContext("2d");
          if (ctx) {
            // Helper to convert number to ordinal (1st, 2nd, 3rd, 4th, etc.)
            const getOrdinal = (n) => {
              const s = ["th", "st", "nd", "rd"];
              const v = n % 100;
              return n + (s[(v - 20) % 10] || s[v] || s[0]);
            };

            // Convert "2025-28" to "1st Week of July"
            const formatWeekLabel = (yearWeek) => {
              const [year, weekNum] = yearWeek.split('-').map(Number);

              const jan1 = new Date(year, 0, 1);
              const daysOffset = (weekNum - 1) * 7;
              const weekStart = new Date(jan1.setDate(jan1.getDate() + daysOffset));
              const month = weekStart.toLocaleString('default', { month: 'long' });

              const weekOfMonth = Math.ceil((weekStart.getDate() + (weekStart.getDay() || 7) - 1) / 7);
              return `${getOrdinal(weekOfMonth)} Week of ${month}`;
            };

            const labels = data.weeklyUploads.map(e => formatWeekLabel(e.week));
            const values = data.weeklyUploads.map(e => e.uploads);

            new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [{
                  label: "Uploads per Week",
                  data: values,
                  backgroundColor: "rgba(75, 192, 192, 0.7)",
                  borderRadius: 5,
                }]
              },
              options: {
                responsive: true,
                scales: {
                  y: {
                    beginAtZero: true,
                    title: { display: true, text: "Uploads" }
                  },
                  x: {
                    title: { display: true, text: "Weekly Summary" }
                  }
                }
              }
            });
          }
        } else {
          renderNoData("weeklyUploadWrapper", "No weekly upload data.");
        }



        // ✅ Region Upload Trend Chart (Enhanced)
        if (data.regionSharedTrend?.length) {
          const labels = data.regionSharedTrend.map(d => d.date);
          const keys = Object.keys(data.regionSharedTrend[0]).filter(k => k !== "date");

          const regionColors = [
            "#ef5350", "#ab47bc", "#5c6bc0", "#29b6f6", "#66bb6a", "#ffa726", "#8d6e63", "#26c6da", "#d4e157", "#ec407a"
          ];

          const datasets = keys.map((k, i) => ({
            label: k,
            data: data.regionSharedTrend.map(x => x[k]),
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            borderColor: regionColors[i % regionColors.length],
            backgroundColor: regionColors[i % regionColors.length] + "33" // 20% transparent
          }));

          new Chart("regionSharedTrendChart", {
            type: "line",
            data: { labels, datasets },
            options: {
              responsive: true,
              plugins: {
                legend: { position: "bottom" },
                tooltip: {
                  mode: "index",
                  intersect: false
                }
              },
              interaction: {
                mode: "nearest",
                axis: "x",
                intersect: false
              },
              scales: {
                y: {
                  beginAtZero: true,
                  ticks: { precision: 0 },
                  title: { display: true, text: "Uploads" }
                },
                x: {
                  title: { display: true, text: "Date" }
                }
              }
            }
          });
        } else {
          renderNoData("regionSharedWrapper", "No region trend data.");
        }
      }


      // === Patient Summary ===
      if (role === "patient") {
        set("statTotal", s.total_uploads);
        set("statAverageAge", s.warnings_sent);
        const el = document.getElementById("statLatest");
        if (el && s.upload_freq) {
          el.innerHTML = `<span style="font-size:18px; font-weight:500;">${s.upload_freq}</span>`;
        }

        const diagEl = document.getElementById("statRegions");
        if (diagEl && s.latest_diagnosis) {
          const [d, t] = s.latest_diagnosis.split(" ");
          diagEl.innerHTML = `<span style="font-weight:bold;">${d}</span><span style="font-size:10px;"> ${t}</span>`;
        }
      }

      // === Ratio Distribution Chart (Patient only) ===
      if (!data.ratioData || (data.ratioData.Positive + data.ratioData.Negative === 0)) {
        renderNoData("ratioWrapper", "No ratio data available.");
      } else {
        const rData = data.ratioData;
        new Chart("ratioChart", {
          type: "doughnut",
          data: {
            labels: ["Conjunctivitis", "Non Conjunctivitis"],
            datasets: [{
              data: [rData.Positive, rData.Negative],
              backgroundColor: ["#ef5350", "#43a047"]
            }]
          },
          options: {
            plugins: {
              legend: { position: "bottom" },
              tooltip: {
                callbacks: {
                  label: ctx => {
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const percent = ((ctx.raw / total) * 100).toFixed(1);
                    return `${ctx.label}: ${ctx.raw} (${percent}%)`;
                  }
                }
              }
            }
          }
        });
      }


      // === Shared Charts ===
      if (role !== "patient") {
        if (!data.genderDistribution?.some(x => x > 0)) renderNoData("genderWrapper", "No gender data.");
        else new Chart("genderChart", {
          type: "doughnut",
          data: {
            labels: ["Female", "Male"],
            datasets: [{
              data: data.genderDistribution,
              backgroundColor: ["#d81b60", "#1e88e5"]
            }]
          },
          options: {
            plugins: {
              tooltip: {
                callbacks: {
                  label: ctx => {
                    const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                    const percent = ((ctx.raw / total) * 100).toFixed(1);
                    return `${ctx.label}: ${ctx.raw} (${percent}%)`;
                  }
                }
              }
            }
          }
        });

        if (!Object.keys(data.ageGroups).length) renderNoData("ageWrapper", "No age data.");
        else new Chart("ageChart", {
          type: "bar",
          data: {
            labels: Object.keys(data.ageGroups),
            datasets: [{
              label: "Patients",
              data: Object.values(data.ageGroups),
              backgroundColor: "#5e35b1"
            }]
          },
          options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
        });
      }
    })
    .catch(err => {
      console.error("Dashboard fetch failed:", err);
    });
});

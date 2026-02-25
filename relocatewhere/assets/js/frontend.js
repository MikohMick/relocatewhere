(function ($) {
    "use strict";

    var DISCLAIMER_KEY = "rw_disclaimer_accepted";

    // ========================================================================
    // State
    // ========================================================================

    var state = {
        countyKey:      "",
        countyName:     "",
        industry:       "",
        allJobs:        [],    // all jobs from last fetch
        filteredJobs:   [],    // after client-side industry filter
        currentPage:    1,
        perPage:        10,
        map:            null,
        countyMarkers:  {},    // countyKey -> L.circleMarker
        isSearching:    false,
    };

    var industries = {
        "Technology":     ["tech", "ict", "software", "developer", "it ", "digital", "data", "systems", "network", "cyber", "programmer", "web ", "mobile"],
        "Finance":        ["finance", "bank", "account", "audit", "treasury", "invest", "insurance", "actuari", "loan", "credit", "financial"],
        "Health":         ["health", "medical", "nurse", "doctor", "clinical", "pharmacy", "lab", "radiolog", "dentist", "physioth", "nutritio", "hospital"],
        "Education":      ["teach", "tutor", "school", "lecturer", "education", "academic", "training", "curriculum", "principal", "headteach"],
        "NGO":            ["ngo", "non-profit", "nonprofit", "humanitarian", "development", "community", "foundation", "charity", "social work", "volunteer"],
        "Engineering":    ["engineer", "civil", "mechanical", "electrical", "structural", "construction", "project manager", "site manager", "quantity survey", "architect"],
        "Sales":          ["sales", "marketing", "business development", "brand", "customer", "retail", "commercial", "revenue", "distribution", "client"],
        "Administration": ["admin", "secretary", "receptionist", "office", "clerk", "pa ", "executive assistant", "operations", "facilities", "procurement"],
        "Agriculture":    ["agri", "farm", "livestock", "crop", "horticulture", "vet", "soil", "irrigation", "food production", "dairy"],
        "Hospitality":    ["hotel", "hospitality", "chef", "cook", "waiter", "tourism", "travel", "lodge", "resort", "catering", "housekeep"],
        "Media":          ["media", "journalist", "reporter", "editor", "broadcast", "radio", "tv ", "television", "content", "public relat", "communications", "writer"],
        "Legal":          ["legal", "lawyer", "advocate", "compliance", "paralegal", "court", "litigation", "contract"],
    };

    // ========================================================================
    // Init
    // ========================================================================

    $(document).ready(function () {
        initMap();
        initCountySearch();
        initIndustryFilter();
        initSearchBtn();
        initClearBtn();
        initDisclaimer();
    });

    // ========================================================================
    // Disclaimer Modal
    // ========================================================================

    function initDisclaimer() {
        var accepted = false;
        try {
            accepted = localStorage.getItem(DISCLAIMER_KEY) === "1";
        } catch (e) {
            // localStorage blocked (private browsing, etc.) — treat as accepted.
            accepted = true;
        }

        if (accepted) {
            // Already seen before — go straight to loading jobs.
            loadJobs(false);
        } else {
            // Show modal, block until user accepts.
            $("#rw-disclaimer-overlay").fadeIn(200);

            $("#rw-disclaimer-accept").on("click", function () {
                try {
                    localStorage.setItem(DISCLAIMER_KEY, "1");
                } catch (e) {}

                $("#rw-disclaimer-overlay").fadeOut(180, function () {
                    // Kick off initial load after modal closes.
                    loadJobs(false);
                });
            });
        }
    }

    // ========================================================================
    // Map
    // ========================================================================

    function initMap() {
        state.map = L.map("rw-map", { zoomControl: true }).setView([-0.5, 37.5], 6);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
            maxZoom: 18,
        }).addTo(state.map);

        addCountyMarkers();
    }

    function addCountyMarkers() {
        $.each(rwData.counties, function (i, county) {
            var marker = L.circleMarker([county.lat, county.lng], {
                radius:      8,
                color:       "#fff",
                weight:      2,
                fillColor:   "#3b82f6",
                fillOpacity: 0.85,
            }).addTo(state.map);

            marker.bindTooltip(county.name, {
                permanent:  false,
                direction:  "top",
                className:  "rw-tooltip",
                offset:     [0, -8],
            });

            marker.on("click", function () {
                selectCounty(county.key, county.name);
            });

            state.countyMarkers[county.key] = marker;
        });
    }

    function highlightMarker(countyKey) {
        $.each(state.countyMarkers, function (key, marker) {
            marker.setStyle({ fillColor: "#3b82f6", radius: 8 });
        });
        if (countyKey && state.countyMarkers[countyKey]) {
            state.countyMarkers[countyKey].setStyle({ fillColor: "#f59e0b", radius: 12 });
            state.countyMarkers[countyKey].bringToFront();
        }
    }

    // ========================================================================
    // County Search (client-side, no AJAX needed)
    // ========================================================================

    function initCountySearch() {
        var $input    = $("#rw-county-input");
        var $dropdown = $("#rw-county-dropdown");
        var $hidden   = $("#rw-county-key");

        $input.on("focus", function () {
            renderCountyDropdown($input.val());
        });

        $input.on("input", function () {
            $hidden.val("");
            state.countyKey  = "";
            state.countyName = "";
            renderCountyDropdown($input.val());
        });

        $input.on("keydown", function (e) {
            var $items       = $dropdown.find(".rw-dropdown-item");
            var $highlighted = $items.filter(".highlighted");
            var idx          = $items.index($highlighted);

            if (e.key === "ArrowDown") {
                e.preventDefault();
                idx = Math.min(idx + 1, $items.length - 1);
                $items.removeClass("highlighted").eq(idx).addClass("highlighted");
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                idx = Math.max(idx - 1, 0);
                $items.removeClass("highlighted").eq(idx).addClass("highlighted");
            } else if (e.key === "Enter") {
                e.preventDefault();
                if ($highlighted.length) $highlighted.trigger("click");
            } else if (e.key === "Escape") {
                $dropdown.hide();
            }
        });

        $(document).on("click", function (e) {
            if (!$(e.target).closest(".rw-county-search-wrap").length) {
                $dropdown.hide();
            }
        });
    }

    function renderCountyDropdown(query) {
        var $dropdown = $("#rw-county-dropdown");
        var q         = (query || "").toLowerCase().trim();
        var matches   = [];

        $.each(rwData.counties, function (i, county) {
            if (!q || county.name.toLowerCase().indexOf(q) !== -1) {
                matches.push(county);
            }
        });

        var html = '<div class="rw-dropdown-item" data-key="" data-name="">All Kenya</div>';

        if (matches.length === 0) {
            $dropdown.html('<div class="rw-dropdown-item" style="color:#9ca3af;">No counties found</div>').show();
            return;
        }

        $.each(matches.slice(0, 20), function (i, county) {
            html += '<div class="rw-dropdown-item" data-key="' + escapeHtml(county.key) +
                    '" data-name="' + escapeHtml(county.name) + '">' +
                    escapeHtml(county.name) + " County</div>";
        });

        $dropdown.html(html).show();

        $dropdown.find(".rw-dropdown-item").on("click", function () {
            var key  = $(this).data("key") || "";
            var name = $(this).data("name") || "";
            $("#rw-county-input").val(name ? name + " County" : "");
            $("#rw-county-key").val(key);
            state.countyKey  = key;
            state.countyName = name;
            $dropdown.hide();
        });
    }

    // ========================================================================
    // Industry Filter (client-side)
    // ========================================================================

    function initIndustryFilter() {
        $("#rw-industry-select").on("change", function () {
            state.industry    = $(this).val();
            state.currentPage = 1;
            applyIndustryFilter();
            renderJobs();
        });
    }

    function applyIndustryFilter() {
        if (!state.industry) {
            state.filteredJobs = state.allJobs.slice();
            return;
        }
        var keywords = industries[state.industry] || [state.industry.toLowerCase()];
        state.filteredJobs = state.allJobs.filter(function (job) {
            var haystack = (job.title + " " + job.company).toLowerCase();
            for (var i = 0; i < keywords.length; i++) {
                if (haystack.indexOf(keywords[i]) !== -1) return true;
            }
            return false;
        });
    }

    // ========================================================================
    // Search & Clear buttons
    // ========================================================================

    function initSearchBtn() {
        $("#rw-search-btn").on("click", function () {
            loadJobs(true); // true = scroll to results after load
        });
    }

    function initClearBtn() {
        $("#rw-clear-btn").on("click", function () {
            state.countyKey  = "";
            state.countyName = "";
            state.industry   = "";
            $("#rw-county-input").val("");
            $("#rw-county-key").val("");
            $("#rw-industry-select").val("");
            $("#rw-clear-btn").hide();
            highlightMarker(null);
            loadJobs(false);
        });
    }

    function selectCounty(key, name) {
        state.countyKey  = key;
        state.countyName = name;
        $("#rw-county-input").val(name + " County");
        $("#rw-county-key").val(key);
        highlightMarker(key);
        loadJobs(true); // scroll to results when county clicked on map
    }

    // ========================================================================
    // Search button loading state
    // ========================================================================

    function setSearchBtnLoading(loading) {
        var $btn = $("#rw-search-btn");
        if (loading) {
            $btn.prop("disabled", true).addClass("rw-btn-loading");
            $("#rw-search-icon").hide();
            $("#rw-search-spinner").show();
            $("#rw-search-label").text("Searching\u2026");
        } else {
            $btn.prop("disabled", false).removeClass("rw-btn-loading");
            $("#rw-search-icon").show();
            $("#rw-search-spinner").hide();
            $("#rw-search-label").text("Search");
        }
    }

    // ========================================================================
    // Load Jobs (AJAX)
    // ========================================================================

    /**
     * @param {boolean} scrollToResults  Whether to smooth-scroll to the list after load.
     */
    function loadJobs(scrollToResults) {
        if (state.isSearching) return;
        state.isSearching = true;

        setSearchBtnLoading(true);
        showLoading(true);
        hideEmpty();

        var hasFilter = state.countyKey || state.industry;
        if (hasFilter) {
            $("#rw-clear-btn").show();
        }

        $.ajax({
            url:     rwData.ajaxUrl,
            method:  "POST",
            data:    {
                action: "rw_get_jobs",
                nonce:  rwData.nonce,
                county: state.countyKey,
            },
            success: function (response) {
                state.isSearching = false;
                setSearchBtnLoading(false);
                showLoading(false);

                if (response.success) {
                    state.allJobs     = response.data.jobs || [];
                    state.currentPage = 1;
                    applyIndustryFilter();
                    updateResultsMeta(response.data.location, state.filteredJobs.length);
                    renderJobs();

                    if (scrollToResults) {
                        scrollToList();
                    }
                } else {
                    showEmpty(
                        response.data ? response.data.message : "Failed to load jobs. Please try again."
                    );
                    if (scrollToResults) scrollToList();
                }
            },
            error: function () {
                state.isSearching = false;
                setSearchBtnLoading(false);
                showLoading(false);
                showEmpty("Network error. Please check your connection and try again.");
                if (scrollToResults) scrollToList();
            },
        });
    }

    // ========================================================================
    // Render Jobs
    // ========================================================================

    function updateResultsMeta(location, count) {
        var industry = state.industry
            ? " in <strong>" + escapeHtml(state.industry) + "</strong>"
            : "";
        var loc = location
            ? " &mdash; <strong>" + escapeHtml(location) + "</strong>"
            : " &mdash; <strong>All Kenya</strong>";

        $("#rw-results-meta")
            .html(
                '<span class="rw-job-count">' + count + "</span> job" +
                (count !== 1 ? "s" : "") + industry + loc
            )
            .show();
    }

    function renderJobs() {
        var jobs  = state.filteredJobs;
        var $list = $("#rw-results-list");

        $list.find(".rw-job-card").remove();

        if (jobs.length === 0) {
            if (state.industry) {
                // Industry filter active but no matches — offer to reset to all jobs.
                var industry  = state.industry;
                var inCounty  = state.countyName ? " in " + state.countyName : "";
                showEmpty("No " + industry + " jobs found" + inCounty + ".", true);
            } else {
                showEmpty("No jobs found. Try selecting a different county.");
            }
            $("#rw-pagination").hide();
            updateResultsMeta(state.countyName || "All Kenya", 0);
            return;
        }

        updateResultsMeta(state.countyName || "All Kenya", jobs.length);

        var totalPages = Math.ceil(jobs.length / state.perPage);
        var start      = (state.currentPage - 1) * state.perPage;
        var paginated  = jobs.slice(start, start + state.perPage);

        $.each(paginated, function (i, job) {
            $list.append(buildJobCard(job));
        });

        renderPagination(totalPages, jobs.length);
    }

    function buildJobCard(job) {
        var companyHtml = job.company
            ? '<span class="rw-job-company">' + escapeHtml(job.company) + "</span>" +
              '<span class="rw-job-sep">&middot;</span>'
            : "";

        var locationHtml = job.location
            ? '<span class="rw-job-location">' +
              '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>' +
              escapeHtml(job.location) + "</span>" +
              '<span class="rw-job-sep">&middot;</span>'
            : "";

        var dateHtml = job.date
            ? '<span class="rw-job-date">' + escapeHtml(job.date) + "</span>"
            : "";

        var sourceHtml = job.source_name
            ? '<a class="rw-job-source" href="' + escapeHtml(job.source_url || "#") +
              '" target="_blank" rel="noopener noreferrer">' +
              'via ' + escapeHtml(job.source_name) +
              '<svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>' +
              "</a>"
            : "";

        return (
            '<div class="rw-job-card">' +
                '<div class="rw-job-main">' +
                    '<div class="rw-job-info">' +
                        '<a class="rw-job-title" href="' + escapeHtml(job.url) +
                            '" target="_blank" rel="noopener noreferrer">' +
                            escapeHtml(job.title) +
                        "</a>" +
                        '<div class="rw-job-meta">' +
                            companyHtml + locationHtml + dateHtml +
                            (dateHtml && sourceHtml ? '<span class="rw-job-sep">&middot;</span>' : "") +
                            sourceHtml +
                        "</div>" +
                    "</div>" +
                    '<a class="rw-apply-btn" href="' + escapeHtml(job.url) +
                        '" target="_blank" rel="noopener noreferrer">' +
                        "Apply " +
                        '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>' +
                    "</a>" +
                "</div>" +
            "</div>"
        );
    }

    // ========================================================================
    // Pagination
    // ========================================================================

    function renderPagination(totalPages, totalJobs) {
        var $pag = $("#rw-pagination");

        if (totalPages <= 1) {
            $pag.hide();
            return;
        }

        var html    = "";
        var start   = Math.max(1, state.currentPage - 2);
        var end     = Math.min(totalPages, state.currentPage + 2);

        html += '<button class="rw-page-btn rw-page-prev"' +
                (state.currentPage <= 1 ? " disabled" : "") + ">&laquo; Prev</button>";

        if (start > 1) {
            html += '<button class="rw-page-btn" data-page="1">1</button>';
            if (start > 2) html += '<span class="rw-page-ellipsis">&hellip;</span>';
        }

        for (var p = start; p <= end; p++) {
            html += '<button class="rw-page-btn' + (p === state.currentPage ? " active" : "") +
                    '" data-page="' + p + '">' + p + "</button>";
        }

        if (end < totalPages) {
            if (end < totalPages - 1) html += '<span class="rw-page-ellipsis">&hellip;</span>';
            html += '<button class="rw-page-btn" data-page="' + totalPages + '">' + totalPages + "</button>";
        }

        html += '<button class="rw-page-btn rw-page-next"' +
                (state.currentPage >= totalPages ? " disabled" : "") + ">Next &raquo;</button>";

        html += '<span class="rw-page-info">Showing ' +
                ((state.currentPage - 1) * state.perPage + 1) + "&ndash;" +
                Math.min(state.currentPage * state.perPage, totalJobs) +
                " of " + totalJobs + " jobs</span>";

        $pag.html(html).show();

        $pag.find(".rw-page-btn[data-page]").on("click", function () {
            state.currentPage = parseInt($(this).data("page"), 10);
            renderJobs();
            scrollToList();
        });

        $pag.find(".rw-page-prev").on("click", function () {
            if (state.currentPage > 1) {
                state.currentPage--;
                renderJobs();
                scrollToList();
            }
        });

        $pag.find(".rw-page-next").on("click", function () {
            if (state.currentPage < totalPages) {
                state.currentPage++;
                renderJobs();
                scrollToList();
            }
        });
    }

    function scrollToList() {
        var $target = $("#rw-results-list");
        if ($target.length) {
            $("html, body").animate({ scrollTop: $target.offset().top - 24 }, 420, "swing");
        }
    }

    // ========================================================================
    // UI helpers
    // ========================================================================

    function showLoading(show) {
        if (show) {
            $("#rw-loading").show();
            $("#rw-results-list .rw-job-card").remove();
            $("#rw-pagination").hide();
            $("#rw-results-meta").hide();
        } else {
            $("#rw-loading").hide();
        }
    }

    function hideEmpty() {
        $("#rw-empty").hide();
    }

    /**
     * @param {string}  msg          Message text.
     * @param {boolean} showResetBtn When true, show a "Show all jobs" button that clears the industry filter.
     */
    function showEmpty(msg, showResetBtn) {
        $("#rw-empty-msg").text(msg || "No jobs found.");

        if (showResetBtn) {
            var locationLabel = state.countyName || "All Kenya";
            var $btn = $('<button class="rw-btn rw-btn-outline">Show all jobs in ' + escapeHtml(locationLabel) + "</button>");
            $btn.on("click", function () {
                state.industry    = "";
                state.currentPage = 1;
                $("#rw-industry-select").val("");
                applyIndustryFilter();
                renderJobs();
            });
            $("#rw-empty-action").empty().append($btn);
        } else {
            $("#rw-empty-action").empty();
        }

        $("#rw-empty").show();
        $("#rw-pagination").hide();
    }

    // ========================================================================
    // Utilities
    // ========================================================================

    function escapeHtml(str) {
        if (!str) return "";
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

})(jQuery);

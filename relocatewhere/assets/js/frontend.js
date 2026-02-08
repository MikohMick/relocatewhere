(function ($) {
    "use strict";

    // State
    var state = {
        county: null,
        countyKey: "",
        householdType: "",
        incomeRange: "",
        currency: rwData.defaultCurrency || "KES",
        exchangeRate: rwData.exchangeRate || 154,
        results: null,
        map: null,
        markers: [],
    };

    // Cost category labels & icons
    var costLabels = {
        rent: "Estimated Rent",
        food: "Food & Groceries",
        transport: "Transport",
        utilities: "Utilities",
        entertainment: "Entertainment",
        health: "Health",
        other: "Other",
    };

    var costIcons = {
        rent: "&#127968;",
        food: "&#127834;",
        transport: "&#128652;",
        utilities: "&#9889;",
        entertainment: "&#127916;",
        health: "&#9764;",
        other: "&#128230;",
    };

    // ========================================================================
    // Initialization
    // ========================================================================

    $(document).ready(function () {
        initAccordion();
        initCountySearch();
        initHouseholdSelection();
        initIncomeSelection();
        initEmailStep();
        initCurrencyToggle();
        initContributorForm();
        checkContributorToken();
    });

    // ========================================================================
    // Accordion Logic
    // ========================================================================

    function initAccordion() {
        $(".rw-accordion-header").on("click", function () {
            var $item = $(this).closest(".rw-accordion-item");
            var step = parseInt($item.data("step"), 10);

            // Only allow clicking completed steps or the next available step.
            if ($item.hasClass("completed") || $item.hasClass("active")) {
                openStep(step);
            }
        });
    }

    function openStep(step) {
        $(".rw-accordion-item").each(function () {
            var $item = $(this);
            var itemStep = parseInt($item.data("step"), 10);

            if (itemStep === step) {
                $item.addClass("active");
            } else {
                $item.removeClass("active");
            }
        });
    }

    function completeStep(step, summaryText) {
        var $item = $('.rw-accordion-item[data-step="' + step + '"]');
        $item.addClass("completed").removeClass("active");
        $item.find(".rw-step-summary").text(summaryText).show();
    }

    // ========================================================================
    // Step 1: County Search
    // ========================================================================

    function initCountySearch() {
        var $input = $("#rw-county-input");
        var $dropdown = $("#rw-county-dropdown");
        var $hidden = $("#rw-county-key");
        var $nextBtn = $('.rw-step[data-step="1"] .rw-btn-next');
        var debounceTimer;

        $input.on("focus", function () {
            doCountySearch($input.val());
        });

        $input.on("input", function () {
            clearTimeout(debounceTimer);
            var query = $input.val();
            $hidden.val("");
            $nextBtn.prop("disabled", true);

            debounceTimer = setTimeout(function () {
                doCountySearch(query);
            }, 200);
        });

        // Keyboard nav.
        $input.on("keydown", function (e) {
            var $items = $dropdown.find(".rw-dropdown-item");
            var $highlighted = $items.filter(".highlighted");
            var idx = $items.index($highlighted);

            if (e.key === "ArrowDown") {
                e.preventDefault();
                idx = Math.min(idx + 1, $items.length - 1);
                $items.removeClass("highlighted").eq(idx).addClass("highlighted");
                scrollIntoViewIfNeeded($items.eq(idx), $dropdown);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                idx = Math.max(idx - 1, 0);
                $items.removeClass("highlighted").eq(idx).addClass("highlighted");
                scrollIntoViewIfNeeded($items.eq(idx), $dropdown);
            } else if (e.key === "Enter") {
                e.preventDefault();
                if ($highlighted.length) {
                    $highlighted.trigger("click");
                }
            } else if (e.key === "Escape") {
                $dropdown.hide();
            }
        });

        // Close dropdown on outside click.
        $(document).on("click", function (e) {
            if (!$(e.target).closest(".rw-county-search-wrap").length) {
                $dropdown.hide();
            }
        });
    }

    function doCountySearch(query) {
        var $dropdown = $("#rw-county-dropdown");

        $.ajax({
            url: rwData.ajaxUrl,
            data: {
                action: "rw_search_counties",
                nonce: rwData.nonce,
                query: query,
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    var html = "";
                    $.each(response.data, function (i, county) {
                        html +=
                            '<div class="rw-dropdown-item" data-key="' +
                            escapeHtml(county.key) +
                            '" data-name="' +
                            escapeHtml(county.name) +
                            '">' +
                            escapeHtml(county.name) +
                            " County</div>";
                    });
                    $dropdown.html(html).show();

                    $dropdown.find(".rw-dropdown-item").on("click", function () {
                        var key = $(this).data("key");
                        var name = $(this).data("name");
                        selectCounty(key, name);
                    });
                } else {
                    $dropdown
                        .html(
                            '<div class="rw-dropdown-item" style="color:#9ca3af;">No counties found</div>'
                        )
                        .show();
                }
            },
        });
    }

    function selectCounty(key, name) {
        $("#rw-county-input").val(name + " County");
        $("#rw-county-key").val(key);
        $("#rw-county-dropdown").hide();
        state.countyKey = key;
        state.county = name;

        var $nextBtn = $('.rw-step[data-step="1"] .rw-btn-next');
        $nextBtn.prop("disabled", false);

        $nextBtn.off("click").on("click", function () {
            completeStep(1, name + " County");
            openStep(2);
        });
    }

    // ========================================================================
    // Step 2: Household Type
    // ========================================================================

    function initHouseholdSelection() {
        var $nextBtn = $('.rw-step[data-step="2"] .rw-btn-next');

        $(".rw-option-card").on("click", function () {
            $(".rw-option-card").removeClass("selected");
            $(this).addClass("selected");
            $(this).find("input[type=radio]").prop("checked", true);
            state.householdType = $(this).data("value");
            $nextBtn.prop("disabled", false);
        });

        $nextBtn.on("click", function () {
            var labels = {
                single: "Just me",
                couple: "With partner",
                family: "Family with kids",
            };
            completeStep(2, labels[state.householdType] || state.householdType);
            openStep(3);
        });
    }

    // ========================================================================
    // Step 3: Income
    // ========================================================================

    function initIncomeSelection() {
        var $select = $("#rw-income-range");
        var $nextBtn = $('.rw-step[data-step="3"] .rw-btn-next');

        $select.on("change", function () {
            state.incomeRange = $(this).val();
            $nextBtn.prop("disabled", !state.incomeRange);
        });

        $nextBtn.on("click", function () {
            var text = $select.find("option:selected").text();
            completeStep(3, text);
            openStep(4);

            // Set disclaimer text.
            $("#rw-disclaimer-text").text(rwData.disclaimer);
        });
    }

    // ========================================================================
    // Step 4: Email + Skip
    // ========================================================================

    function initEmailStep() {
        $("#rw-subscribe-btn").on("click", function () {
            var email = $("#rw-email").val().trim();
            if (!email || !isValidEmail(email)) {
                alert("Please enter a valid email address.");
                return;
            }

            var $btn = $(this);
            $btn.prop("disabled", true).text("Saving...");

            $.ajax({
                url: rwData.ajaxUrl,
                method: "POST",
                data: {
                    action: "rw_save_email",
                    nonce: rwData.nonce,
                    email: email,
                    county: state.countyKey,
                    household_type: state.householdType,
                    income_range: state.incomeRange,
                },
                success: function (response) {
                    $btn.prop("disabled", false).text("Subscribe & See Results");
                    if (response.success) {
                        completeStep(4, "Subscribed: " + email);
                        loadResults();
                    } else {
                        alert(response.data.message || "Something went wrong.");
                    }
                },
                error: function () {
                    $btn.prop("disabled", false).text("Subscribe & See Results");
                    alert("Network error. Please try again.");
                },
            });
        });

        $("#rw-skip-btn").on("click", function () {
            completeStep(4, "Skipped email");
            loadResults();
        });
    }

    // ========================================================================
    // Load Results
    // ========================================================================

    function loadResults() {
        $(".rw-form-section").hide();
        var $results = $("#rw-results-section");
        $results.show();

        // Scroll to results.
        $("html, body").animate(
            { scrollTop: $results.offset().top - 20 },
            400
        );

        // Update header.
        $("#rw-results-county").text(state.county + " County");
        var householdLabels = {
            single: "1 person household",
            couple: "2 person household",
            family: "Family (4 person) household",
        };
        $("#rw-results-meta-text").text(
            (householdLabels[state.householdType] || "") +
                " \u00B7 " +
                formatIncomeRange(state.incomeRange)
        );

        // Show loading.
        $("#rw-loading").show();

        // Init map.
        initMap();

        $.ajax({
            url: rwData.ajaxUrl,
            method: "POST",
            data: {
                action: "rw_get_results",
                nonce: rwData.nonce,
                county: state.countyKey,
                household_type: state.householdType,
                income_range: state.incomeRange,
            },
            success: function (response) {
                $("#rw-loading").hide();
                if (response.success) {
                    state.results = response.data;
                    state.exchangeRate = response.data.exchange_rate || state.exchangeRate;
                    renderResults(response.data);
                } else {
                    $("#rw-results-list").html(
                        '<div class="rw-message error">' +
                            escapeHtml(
                                response.data.message || "Failed to load results."
                            ) +
                            "</div>"
                    );
                }
            },
            error: function () {
                $("#rw-loading").hide();
                $("#rw-results-list").html(
                    '<div class="rw-message error">Network error. Please try again later.</div>'
                );
            },
        });
    }

    // ========================================================================
    // Render Results
    // ========================================================================

    function renderResults(data) {
        var towns = data.towns || [];
        var $list = $("#rw-results-list");
        $list.empty();

        if (towns.length === 0) {
            $list.html(
                '<div class="rw-message">No cost-of-living data available for this area yet.</div>'
            );
            return;
        }

        // Sort by total cost ascending.
        towns.sort(function (a, b) {
            return (a.costs ? a.costs.total : 0) - (b.costs ? b.costs.total : 0);
        });

        $.each(towns, function (i, town) {
            $list.append(buildTownCard(town, i));
        });

        // Add markers to map.
        addMapMarkers(towns, data.county);

        // Bind show more toggles.
        $(".rw-show-more-btn").on("click", function () {
            var $card = $(this).closest(".rw-town-card");
            $card.toggleClass("expanded");
            $(this).text(
                $card.hasClass("expanded") ? "Show less" : "Show more"
            );
        });
    }

    function buildTownCard(town, index) {
        var costs = town.costs || {};
        var total = costs.total || 0;
        var totalUsd = Math.round(total / state.exchangeRate);

        var affordClass = "rw-badge-comfortable";
        var affordLabel = town.affordability || "comfortable";
        if (affordLabel === "reasonable") affordClass = "rw-badge-reasonable";
        if (affordLabel === "stretched") affordClass = "rw-badge-stretched";

        var safetyClass = (town.safety || "moderate")
            .toLowerCase()
            .replace(/\s+/g, "-");

        // Build tags HTML.
        var tagsHtml =
            '<span class="rw-tag rw-tag-safety ' +
            escapeHtml(safetyClass) +
            '">' +
            escapeHtml(town.safety || "Moderate") +
            "</span>";
        if (town.tags) {
            $.each(town.tags, function (j, tag) {
                tagsHtml +=
                    '<span class="rw-tag">' + escapeHtml(tag) + "</span>";
            });
        }

        // Build cost breakdown.
        var breakdownHtml = "";
        var costKeys = [
            "rent",
            "food",
            "transport",
            "utilities",
            "entertainment",
            "health",
            "other",
        ];
        $.each(costKeys, function (j, key) {
            var val = costs[key] || 0;
            breakdownHtml +=
                '<div class="rw-cost-item">' +
                '<span class="rw-cost-label">' +
                costIcons[key] +
                " " +
                costLabels[key] +
                "</span>" +
                '<span class="rw-cost-value" data-kes="' +
                val +
                '">' +
                formatCurrency(val) +
                "</span>" +
                "</div>";
        });

        // Source bar.
        var aiPct = town.ai_source_pct !== undefined ? town.ai_source_pct : 100;
        var humanPct =
            town.human_source_pct !== undefined ? town.human_source_pct : 0;

        var sourceHtml =
            '<div class="rw-source-info">' +
            '<div class="rw-source-bar">' +
            '<div class="rw-source-ai" style="width:' +
            aiPct +
            '%"></div>' +
            '<div class="rw-source-human" style="width:' +
            humanPct +
            '%"></div>' +
            "</div>" +
            '<div class="rw-source-labels">' +
            '<span class="rw-source-label-ai">' +
            aiPct +
            "% AI</span>" +
            (humanPct > 0
                ? '<span class="rw-source-label-human">' +
                  humanPct +
                  "% Human</span>"
                : "") +
            "</div>" +
            "</div>";

        var html =
            '<div class="rw-town-card" data-index="' +
            index +
            '">' +
            '<div class="rw-town-card-header">' +
            '<div class="rw-town-info">' +
            '<h3 class="rw-town-name">' +
            escapeHtml(town.name) +
            "</h3>" +
            '<p class="rw-town-desc">' +
            escapeHtml(town.description || "") +
            "</p>" +
            '<div class="rw-town-tags">' +
            tagsHtml +
            "</div>" +
            "</div>" +
            '<div class="rw-town-cost">' +
            '<span class="rw-affordability-badge ' +
            affordClass +
            '">' +
            escapeHtml(town.income_pct || 0) +
            "% of income</span>" +
            '<p class="rw-total-cost" data-kes="' +
            total +
            '">' +
            formatCurrency(total) +
            '<span style="font-size:13px;font-weight:400;color:#6b7280"> /mo</span></p>' +
            '<p class="rw-total-cost-usd" data-kes="' +
            total +
            '">~' +
            formatCurrencyAlt(total) +
            "</p>" +
            "</div>" +
            "</div>" +
            '<div class="rw-town-details">' +
            '<div class="rw-cost-breakdown">' +
            breakdownHtml +
            "</div>" +
            sourceHtml +
            '<div class="rw-useful-links-town">' +
            '<p class="rw-coming-soon" style="font-size:13px;">Useful links for ' +
            escapeHtml(town.name) +
            " &mdash; Coming soon</p>" +
            "</div>" +
            "</div>" +
            '<button class="rw-show-more-btn">Show more</button>' +
            "</div>";

        return html;
    }

    // ========================================================================
    // Map
    // ========================================================================

    function initMap() {
        if (state.map) {
            state.map.remove();
        }

        state.map = L.map("rw-map").setView([-1.2921, 36.8219], 7);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
            maxZoom: 18,
        }).addTo(state.map);
    }

    function addMapMarkers(towns, county) {
        // Clear existing markers.
        $.each(state.markers, function (i, m) {
            state.map.removeLayer(m);
        });
        state.markers = [];

        if (!county) return;

        var bounds = [];

        // Generate approximate positions around the county center.
        var centerLat = county.lat;
        var centerLng = county.lng;

        $.each(towns, function (i, town) {
            // Spread towns around the county center.
            var angle = (2 * Math.PI * i) / towns.length;
            var radius = 0.05 + Math.random() * 0.08;
            var lat = centerLat + radius * Math.sin(angle);
            var lng = centerLng + radius * Math.cos(angle);

            var total = town.costs ? town.costs.total : 0;
            var totalDisplay = formatCurrencyShort(total);

            var markerClass = "rw-marker-comfortable";
            if (town.affordability === "reasonable")
                markerClass = "rw-marker-reasonable";
            if (town.affordability === "stretched")
                markerClass = "rw-marker-stretched";

            var icon = L.divIcon({
                className: "rw-map-marker",
                html:
                    '<span class="rw-marker-label ' +
                    markerClass +
                    '">' +
                    escapeHtml(totalDisplay) +
                    "</span>",
                iconSize: [80, 30],
                iconAnchor: [40, 15],
            });

            var marker = L.marker([lat, lng], { icon: icon }).addTo(state.map);

            marker.bindPopup(
                "<strong>" +
                    escapeHtml(town.name) +
                    "</strong><br>" +
                    formatCurrency(total) +
                    "/mo<br>" +
                    '<span style="font-size:12px;color:#6b7280;">' +
                    escapeHtml(town.description || "") +
                    "</span>"
            );

            // On marker click, scroll to and expand the town card.
            marker.on("click", function () {
                var $card = $(".rw-town-card").eq(i);
                if ($card.length) {
                    $card.addClass("expanded");
                    $card.find(".rw-show-more-btn").text("Show less");
                    $("html, body").animate(
                        { scrollTop: $card.offset().top - 20 },
                        400
                    );
                }
            });

            state.markers.push(marker);
            bounds.push([lat, lng]);
        });

        if (bounds.length > 0) {
            state.map.fitBounds(bounds, { padding: [30, 30] });
        }
    }

    // ========================================================================
    // Currency Toggle
    // ========================================================================

    function initCurrencyToggle() {
        $("#rw-currency-switch").on("change", function () {
            state.currency = this.checked ? "USD" : "KES";
            var $container = $(".rw-container");

            if (state.currency === "USD") {
                $container.addClass("currency-usd");
            } else {
                $container.removeClass("currency-usd");
            }

            // Update all displayed values.
            updateAllCurrencyValues();
        });
    }

    function updateAllCurrencyValues() {
        $(".rw-total-cost[data-kes]").each(function () {
            var kes = parseInt($(this).data("kes"), 10);
            $(this).html(
                formatCurrency(kes) +
                    '<span style="font-size:13px;font-weight:400;color:#6b7280"> /mo</span>'
            );
        });

        $(".rw-total-cost-usd[data-kes]").each(function () {
            var kes = parseInt($(this).data("kes"), 10);
            $(this).text("~" + formatCurrencyAlt(kes));
        });

        $(".rw-cost-value[data-kes]").each(function () {
            var kes = parseInt($(this).data("kes"), 10);
            $(this).text(formatCurrency(kes));
        });

        // Update map markers.
        if (state.results && state.results.towns) {
            $.each(state.markers, function (i, marker) {
                if (state.results.towns[i]) {
                    var total = state.results.towns[i].costs
                        ? state.results.towns[i].costs.total
                        : 0;
                    var markerClass = "rw-marker-comfortable";
                    if (state.results.towns[i].affordability === "reasonable")
                        markerClass = "rw-marker-reasonable";
                    if (state.results.towns[i].affordability === "stretched")
                        markerClass = "rw-marker-stretched";

                    var newIcon = L.divIcon({
                        className: "rw-map-marker",
                        html:
                            '<span class="rw-marker-label ' +
                            markerClass +
                            '">' +
                            escapeHtml(formatCurrencyShort(total)) +
                            "</span>",
                        iconSize: [80, 30],
                        iconAnchor: [40, 15],
                    });
                    marker.setIcon(newIcon);
                }
            });
        }
    }

    // ========================================================================
    // Contributor Form
    // ========================================================================

    function checkContributorToken() {
        var urlParams = new URLSearchParams(window.location.search);
        var token = urlParams.get("rw_token");

        if (token) {
            $(".rw-form-section").hide();
            $("#rw-results-section").hide();
            $("#rw-contributor-form").show();
            $("#rw-contrib-token").val(token);

            // Init county search for contributor form.
            initContribCountySearch();
        }
    }

    function initContribCountySearch() {
        var $input = $("#rw-contrib-county");
        var $dropdown = $("#rw-contrib-county-dropdown");
        var $hidden = $("#rw-contrib-county-key");
        var debounceTimer;

        $input.on("focus", function () {
            doContribCountySearch($input.val());
        });

        $input.on("input", function () {
            clearTimeout(debounceTimer);
            $hidden.val("");
            debounceTimer = setTimeout(function () {
                doContribCountySearch($input.val());
            }, 200);
        });

        $(document).on("click", function (e) {
            if (!$(e.target).closest("#rw-contrib-county, #rw-contrib-county-dropdown").length) {
                $dropdown.hide();
            }
        });
    }

    function doContribCountySearch(query) {
        var $dropdown = $("#rw-contrib-county-dropdown");

        $.ajax({
            url: rwData.ajaxUrl,
            data: {
                action: "rw_search_counties",
                nonce: rwData.nonce,
                query: query,
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    var html = "";
                    $.each(response.data, function (i, county) {
                        html +=
                            '<div class="rw-dropdown-item" data-key="' +
                            escapeHtml(county.key) +
                            '" data-name="' +
                            escapeHtml(county.name) +
                            '">' +
                            escapeHtml(county.name) +
                            " County</div>";
                    });
                    $dropdown.html(html).show();

                    $dropdown.find(".rw-dropdown-item").on("click", function () {
                        var key = $(this).data("key");
                        var name = $(this).data("name");
                        $("#rw-contrib-county").val(name + " County");
                        $("#rw-contrib-county-key").val(key);
                        $dropdown.hide();
                    });
                } else {
                    $dropdown.hide();
                }
            },
        });
    }

    function initContributorForm() {
        $("#rw-contrib-form").on("submit", function (e) {
            e.preventDefault();

            var token = $("#rw-contrib-token").val();
            var countyKey = $("#rw-contrib-county-key").val();
            var town = $("#rw-contrib-town").val().trim();
            var $msg = $("#rw-contrib-message");

            if (!countyKey || !town) {
                showContribMessage("Please fill in county and town.", "error");
                return;
            }

            var postData = {
                action: "rw_submit_contribution",
                nonce: rwData.nonce,
                token: token,
                county_key: countyKey,
                town: town,
            };

            // Collect cost fields.
            var costFields = [
                "rent",
                "food",
                "transport",
                "utilities",
                "entertainment",
                "health",
                "other",
            ];
            var allFilled = true;
            $.each(costFields, function (i, field) {
                var val = $('#rw-contrib-form input[name="' + field + '"]').val();
                if (!val || parseInt(val, 10) <= 0) {
                    allFilled = false;
                }
                postData[field] = val || 0;
            });

            if (!allFilled) {
                showContribMessage("Please fill in all cost fields.", "error");
                return;
            }

            var $btn = $(this).find('button[type="submit"]');
            $btn.prop("disabled", true).text("Submitting...");

            $.ajax({
                url: rwData.ajaxUrl,
                method: "POST",
                data: postData,
                success: function (response) {
                    $btn.prop("disabled", false).text("Submit Contribution");
                    if (response.success) {
                        showContribMessage(response.data.message, "success");
                        $("#rw-contrib-form")[0].reset();
                    } else {
                        showContribMessage(
                            response.data.message || "Submission failed.",
                            "error"
                        );
                    }
                },
                error: function () {
                    $btn.prop("disabled", false).text("Submit Contribution");
                    showContribMessage("Network error. Please try again.", "error");
                },
            });
        });
    }

    function showContribMessage(text, type) {
        var $msg = $("#rw-contrib-message");
        $msg.text(text)
            .removeClass("success error")
            .addClass(type)
            .show();
    }

    // ========================================================================
    // Utilities
    // ========================================================================

    function formatCurrency(kes) {
        if (state.currency === "USD") {
            var usd = Math.round(kes / state.exchangeRate);
            return "$" + numberWithCommas(usd);
        }
        return "KES " + numberWithCommas(kes);
    }

    function formatCurrencyAlt(kes) {
        if (state.currency === "USD") {
            return "KES " + numberWithCommas(kes);
        }
        var usd = Math.round(kes / state.exchangeRate);
        return "$" + numberWithCommas(usd);
    }

    function formatCurrencyShort(kes) {
        var val = state.currency === "USD" ? Math.round(kes / state.exchangeRate) : kes;
        var prefix = state.currency === "USD" ? "$" : "";

        if (val >= 1000000) {
            return prefix + (val / 1000000).toFixed(1) + "M";
        } else if (val >= 1000) {
            var k = Math.round(val / 1000);
            var remainder = val % 1000;
            if (remainder === 0) {
                return prefix + k + "k";
            }
            return prefix + (val / 1000).toFixed(0) + "k";
        }
        return prefix + val;
    }

    function formatIncomeRange(range) {
        if (!range) return "";
        var parts = range.split("-");
        return "KES " + numberWithCommas(parseInt(parts[0], 10)) + " - " + numberWithCommas(parseInt(parts[1], 10));
    }

    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function escapeHtml(str) {
        if (!str) return "";
        var div = document.createElement("div");
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function scrollIntoViewIfNeeded($el, $container) {
        if (!$el.length) return;
        var elTop = $el.position().top;
        var elBottom = elTop + $el.outerHeight();
        var containerHeight = $container.height();

        if (elBottom > containerHeight) {
            $container.scrollTop($container.scrollTop() + elBottom - containerHeight);
        } else if (elTop < 0) {
            $container.scrollTop($container.scrollTop() + elTop);
        }
    }
})(jQuery);

<?php
/**
 * Frontend template for myjobmap.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$adsense_code = get_option( 'rw_adsense_code', '' );
$donate_link  = get_option( 'rw_donate_link', '' );
?>

<!-- =====================================================================
     Disclaimer Modal (shown once; accepted state stored in localStorage)
     ===================================================================== -->
<div id="rw-disclaimer-overlay" class="rw-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="rw-modal-title" style="display:none;">
    <div class="rw-modal">
        <h3 id="rw-modal-title" class="rw-modal-title">A quick note</h3>
        <p class="rw-modal-body">
            myjobmap is a job discovery tool that surfaces listings from public sources across the web.
            Listings are shown as-is and may not always be current &mdash; verify details directly with the employer before applying.
            We are not a recruiter and take no responsibility for any listing or application outcome.
        </p>
        <button id="rw-disclaimer-accept" class="rw-btn rw-btn-primary rw-btn-block">
            Got it, show me jobs
        </button>
    </div>
</div>


<div id="rw-app" class="rw-container">

    <!-- Header -->
    <div class="rw-header">
        <h2 class="rw-title">myjobmap</h2>
        <p class="rw-subtitle">Find jobs across Kenya's 47 counties</p>
    </div>

    <!-- Filter Bar -->
    <div class="rw-filter-bar">
        <div class="rw-filter-group">
            <label class="rw-filter-label" for="rw-county-input">County</label>
            <div class="rw-county-search-wrap">
                <input
                    type="text"
                    id="rw-county-input"
                    class="rw-input"
                    placeholder="All Kenya"
                    autocomplete="off"
                />
                <div id="rw-county-dropdown" class="rw-dropdown" style="display:none;"></div>
                <input type="hidden" id="rw-county-key" value="" />
            </div>
        </div>

        <div class="rw-filter-group">
            <label class="rw-filter-label" for="rw-industry-select">Industry</label>
            <select id="rw-industry-select" class="rw-select">
                <option value="">All Industries</option>
                <option value="Technology">Technology / ICT</option>
                <option value="Finance">Finance / Banking</option>
                <option value="Health">Healthcare / Medical</option>
                <option value="Education">Education / Teaching</option>
                <option value="NGO">NGO / Development</option>
                <option value="Engineering">Engineering</option>
                <option value="Sales">Sales / Marketing</option>
                <option value="Administration">Administration</option>
                <option value="Agriculture">Agriculture</option>
                <option value="Hospitality">Hospitality / Tourism</option>
                <option value="Media">Media / Communications</option>
                <option value="Legal">Legal</option>
            </select>
        </div>

        <div class="rw-filter-actions">
            <button id="rw-search-btn" class="rw-btn rw-btn-primary">
                <svg id="rw-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <svg id="rw-search-spinner" class="rw-btn-spinner" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:none;"><circle cx="12" cy="12" r="10" stroke-dasharray="60" stroke-dashoffset="40"/></svg>
                <span id="rw-search-label">Search</span>
            </button>
            <button id="rw-clear-btn" class="rw-btn rw-btn-ghost" style="display:none;">
                Clear
            </button>
        </div>
    </div>

    <!-- Results Layout: List + Map -->
    <div class="rw-results-layout">

        <!-- Left: Job List -->
        <div class="rw-results-col">
            <div class="rw-results-meta" id="rw-results-meta"></div>

            <div id="rw-results-list" class="rw-results-list">
                <!-- Loading state -->
                <div id="rw-loading" class="rw-loading" style="display:none;">
                    <div class="rw-spinner"></div>
                    <p>Searching for jobs&hellip;</p>
                </div>

                <!-- Empty / error state -->
                <div id="rw-empty" class="rw-empty" style="display:none;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <p id="rw-empty-msg">No jobs found. Try a different county or industry.</p>
                    <a href="https://www.myjobmag.co.ke" target="_blank" rel="noopener noreferrer" class="rw-btn rw-btn-outline">
                        Browse all jobs on myjobmag &rarr;
                    </a>
                </div>

                <!-- Jobs rendered by JS -->
            </div>

            <div id="rw-pagination" class="rw-pagination" style="display:none;"></div>
        </div>

        <!-- Right: Map -->
        <div class="rw-map-wrap">
            <div id="rw-map" class="rw-map"></div>
            <p class="rw-map-hint">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                Click a county marker to filter jobs
            </p>
        </div>

    </div>

    <!-- AdSense -->
    <?php if ( ! empty( $adsense_code ) ) : ?>
        <div class="rw-ad-slot"><?php echo $adsense_code; ?></div>
    <?php endif; ?>

    <!-- Donate -->
    <?php if ( ! empty( $donate_link ) ) : ?>
        <div class="rw-donate-section">
            <p>Help keep myjobmap free and running</p>
            <a href="<?php echo esc_url( $donate_link ); ?>" class="rw-btn rw-btn-donate" target="_blank" rel="noopener noreferrer">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                Donate
            </a>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="rw-footer">
        <div class="rw-footer-links">
            <span>Powered by myjobmap</span>
            <span class="rw-footer-sep">&middot;</span>
            <span>Listings sourced from public job boards. Always verify with the employer.</span>
        </div>
    </div>

</div>

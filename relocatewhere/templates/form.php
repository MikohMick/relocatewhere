<?php
/**
 * Frontend template for the RelocateWhere tool.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="rw-app" class="rw-container">

    <!-- Header -->
    <div class="rw-header">
        <h2 class="rw-title">Where should you live in Kenya?</h2>
        <p class="rw-subtitle">Find affordable towns that match your budget and lifestyle</p>
    </div>

    <!-- Accordion Form -->
    <div class="rw-form-section">

        <!-- Step 1: County -->
        <div class="rw-accordion-item rw-step active" data-step="1">
            <div class="rw-accordion-header">
                <div class="rw-step-indicator">
                    <span class="rw-step-number">1</span>
                    <span class="rw-step-check">&#10003;</span>
                </div>
                <div class="rw-accordion-title">
                    <h3>Where do you want to move?</h3>
                    <p class="rw-step-summary" style="display:none;"></p>
                </div>
                <span class="rw-accordion-arrow">&#9662;</span>
            </div>
            <div class="rw-accordion-body">
                <div class="rw-county-search-wrap">
                    <input
                        type="text"
                        id="rw-county-input"
                        class="rw-input"
                        placeholder="Start typing a county name..."
                        autocomplete="off"
                    />
                    <div id="rw-county-dropdown" class="rw-dropdown" style="display:none;"></div>
                    <input type="hidden" id="rw-county-key" value="" />
                </div>
                <button class="rw-btn rw-btn-next" data-next="2" disabled>Next</button>
            </div>
        </div>

        <!-- Step 2: Household Type -->
        <div class="rw-accordion-item rw-step" data-step="2">
            <div class="rw-accordion-header">
                <div class="rw-step-indicator">
                    <span class="rw-step-number">2</span>
                    <span class="rw-step-check">&#10003;</span>
                </div>
                <div class="rw-accordion-title">
                    <h3>Who's making the move?</h3>
                    <p class="rw-step-summary" style="display:none;"></p>
                </div>
                <span class="rw-accordion-arrow">&#9662;</span>
            </div>
            <div class="rw-accordion-body">
                <div class="rw-household-options">
                    <label class="rw-option-card" data-value="single">
                        <input type="radio" name="rw_household" value="single" />
                        <div class="rw-option-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/>
                                <path d="M6 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                            </svg>
                        </div>
                        <span class="rw-option-label">Just me</span>
                    </label>
                    <label class="rw-option-card" data-value="couple">
                        <input type="radio" name="rw_household" value="couple" />
                        <div class="rw-option-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M16 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                                <path d="M8 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                                <path d="M2 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/>
                                <path d="M14 15h4a4 4 0 0 1 4 4v2"/>
                            </svg>
                        </div>
                        <span class="rw-option-label">With partner</span>
                    </label>
                    <label class="rw-option-card" data-value="family">
                        <input type="radio" name="rw_household" value="family" />
                        <div class="rw-option-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M16 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                <path d="M8 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                <path d="M12 17a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z"/>
                                <path d="M2 21v-1a4 4 0 0 1 4-4"/>
                                <path d="M22 21v-1a4 4 0 0 0-4-4"/>
                                <path d="M8 21v-1a4 4 0 0 1 4-4 4 4 0 0 1 4 4v1"/>
                            </svg>
                        </div>
                        <span class="rw-option-label">Family with kids</span>
                    </label>
                </div>
                <button class="rw-btn rw-btn-next" data-next="3" disabled>Next</button>
            </div>
        </div>

        <!-- Step 3: Income -->
        <div class="rw-accordion-item rw-step" data-step="3">
            <div class="rw-accordion-header">
                <div class="rw-step-indicator">
                    <span class="rw-step-number">3</span>
                    <span class="rw-step-check">&#10003;</span>
                </div>
                <div class="rw-accordion-title">
                    <h3>What's your expected salary / income?</h3>
                    <p class="rw-step-summary" style="display:none;"></p>
                </div>
                <span class="rw-accordion-arrow">&#9662;</span>
            </div>
            <div class="rw-accordion-body">
                <p class="rw-field-hint">Monthly income in Kenya Shillings (KES)</p>
                <select id="rw-income-range" class="rw-select">
                    <option value="">Select your income range</option>
                    <option value="0-30000">Under KES 30,000</option>
                    <option value="30000-50000">KES 30,000 - 50,000</option>
                    <option value="50000-80000">KES 50,000 - 80,000</option>
                    <option value="80000-120000">KES 80,000 - 120,000</option>
                    <option value="120000-200000">KES 120,000 - 200,000</option>
                    <option value="200000-500000">KES 200,000 - 500,000</option>
                    <option value="500000-1000000">KES 500,000+</option>
                </select>
                <button class="rw-btn rw-btn-next rw-btn-primary" data-next="4" disabled>See Results</button>
            </div>
        </div>

        <!-- Step 4: Disclaimer + Email -->
        <div class="rw-accordion-item rw-step" data-step="4">
            <div class="rw-accordion-header">
                <div class="rw-step-indicator">
                    <span class="rw-step-number">4</span>
                    <span class="rw-step-check">&#10003;</span>
                </div>
                <div class="rw-accordion-title">
                    <h3>Almost there!</h3>
                    <p class="rw-step-summary" style="display:none;"></p>
                </div>
                <span class="rw-accordion-arrow">&#9662;</span>
            </div>
            <div class="rw-accordion-body">
                <div class="rw-disclaimer">
                    <div class="rw-disclaimer-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#e67e22" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <p id="rw-disclaimer-text"></p>
                </div>

                <div class="rw-email-section">
                    <p class="rw-email-cta">Want to help improve our data? Leave your email and we'll send you a form to contribute real cost-of-living information from your area.</p>
                    <div class="rw-email-row">
                        <input type="email" id="rw-email" class="rw-input" placeholder="your@email.com" />
                        <button id="rw-subscribe-btn" class="rw-btn rw-btn-secondary">Subscribe & See Results</button>
                    </div>
                    <button id="rw-skip-btn" class="rw-btn rw-btn-link">Skip to results &rarr;</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section (hidden until form is complete) -->
    <div id="rw-results-section" class="rw-results-section" style="display:none;">

        <div class="rw-results-header">
            <h2>Cost of Living in <span id="rw-results-county"></span></h2>
            <div class="rw-results-meta">
                <span class="rw-housing-info" id="rw-results-meta-text"></span>
                <div class="rw-currency-toggle">
                    <label class="rw-toggle">
                        <input type="checkbox" id="rw-currency-switch" />
                        <span class="rw-toggle-slider"></span>
                    </label>
                    <span class="rw-toggle-label">
                        <span class="rw-currency-label-kes">KES</span> / <span class="rw-currency-label-usd">USD</span>
                    </span>
                </div>
            </div>
            <div class="rw-affordability-legend">
                <span class="rw-legend-item"><span class="rw-dot rw-dot-green"></span> &lt;60% comfortable</span>
                <span class="rw-legend-item"><span class="rw-dot rw-dot-orange"></span> 60-80% reasonable</span>
                <span class="rw-legend-item"><span class="rw-dot rw-dot-red"></span> &gt;80% stretched</span>
            </div>

            <div class="rw-safety-note">
                <strong>Note:</strong> Safety ratings are general assessments based on public data and local knowledge. Always research current conditions and trust your own judgment when choosing where to live.
            </div>
        </div>

        <div class="rw-results-layout">
            <!-- Town List -->
            <div class="rw-results-list" id="rw-results-list">
                <div class="rw-loading" id="rw-loading">
                    <div class="rw-spinner"></div>
                    <p>Analyzing cost of living data...</p>
                </div>
            </div>

            <!-- Map -->
            <div class="rw-map-wrap">
                <div id="rw-map" class="rw-map"></div>
            </div>
        </div>

        <!-- Useful Links (coming soon) -->
        <div class="rw-useful-links">
            <h3>Useful Links</h3>
            <p class="rw-coming-soon">Coming soon &mdash; curated resources for movers in Kenya.</p>
        </div>

        <!-- AdSense slot -->
        <?php if ( ! empty( $adsense_code ) ) : ?>
            <div class="rw-ad-slot"><?php echo $adsense_code; ?></div>
        <?php endif; ?>

        <!-- Donate -->
        <?php if ( ! empty( $donate_link ) ) : ?>
            <div class="rw-donate-section">
                <p>Help us improve this tool for everyone</p>
                <a href="<?php echo esc_url( $donate_link ); ?>" class="rw-btn rw-btn-donate" target="_blank" rel="noopener noreferrer">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                    Donate
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contributor Form (private page, shown via token) -->
    <div id="rw-contributor-form" class="rw-contributor-section" style="display:none;">
        <h2>Contribute Local Data</h2>
        <p>Thank you for helping improve our cost-of-living data! Your identity remains private - we only use the data you provide.</p>

        <form id="rw-contrib-form">
            <input type="hidden" id="rw-contrib-token" value="" />

            <div class="rw-form-group">
                <label>County</label>
                <input type="text" id="rw-contrib-county" class="rw-input" placeholder="Start typing..." autocomplete="off" />
                <div id="rw-contrib-county-dropdown" class="rw-dropdown" style="display:none;"></div>
                <input type="hidden" id="rw-contrib-county-key" value="" />
            </div>

            <div class="rw-form-group">
                <label>Town</label>
                <input type="text" id="rw-contrib-town" class="rw-input" placeholder="Town name" />
            </div>

            <h4>Monthly costs in KES</h4>

            <div class="rw-cost-fields">
                <div class="rw-form-group">
                    <label>Rent</label>
                    <input type="number" name="rent" class="rw-input" placeholder="e.g. 15000" />
                </div>
                <div class="rw-form-group">
                    <label>Food & Groceries</label>
                    <input type="number" name="food" class="rw-input" placeholder="e.g. 8000" />
                </div>
                <div class="rw-form-group">
                    <label>Transport</label>
                    <input type="number" name="transport" class="rw-input" placeholder="e.g. 3000" />
                </div>
                <div class="rw-form-group">
                    <label>Utilities</label>
                    <input type="number" name="utilities" class="rw-input" placeholder="e.g. 4000" />
                </div>
                <div class="rw-form-group">
                    <label>Entertainment</label>
                    <input type="number" name="entertainment" class="rw-input" placeholder="e.g. 2000" />
                </div>
                <div class="rw-form-group">
                    <label>Health</label>
                    <input type="number" name="health" class="rw-input" placeholder="e.g. 2000" />
                </div>
                <div class="rw-form-group">
                    <label>Other</label>
                    <input type="number" name="other" class="rw-input" placeholder="e.g. 1500" />
                </div>
            </div>

            <button type="submit" class="rw-btn rw-btn-primary">Submit Contribution</button>
            <div id="rw-contrib-message" class="rw-message" style="display:none;"></div>
        </form>
    </div>

    <!-- Footer -->
    <div class="rw-footer">
        <div class="rw-footer-links">
            <a href="<?php echo esc_url( $privacy_url ); ?>">Privacy Policy & Terms</a>
            <span class="rw-footer-sep">&middot;</span>
            <span class="rw-footer-powered">Powered by RelocateWhere</span>
        </div>
    </div>
</div>

<?php
/**
 * Prefill Test Pagina
 * 
 * Testpagina voor het testen van prefill functionaliteit met vrijBRP register
 * Design gebaseerd op persoonweergave interface
 */

script('openregister', 'prefill-test');
?>

<div id="prefill-test" class="prefill-test-container">
    <!-- Search Bar -->
    <div class="search-bar">
        <div class="search-input-wrapper">
            <input 
                type="text" 
                id="prefill-search-input" 
                placeholder="Zoek op BSN of achternaam..." 
                class="search-input"
                autocomplete="off"
            />
            <button id="prefill-search-btn" class="search-button">
                <span>Zoek</span>
            </button>
        </div>
        <div id="prefill-loading" class="loading-indicator" style="display: none;">
            <div class="spinner"></div>
            <span>Zoeken...</span>
        </div>
        <div id="prefill-error" class="error-message" style="display: none;"></div>
    </div>

    <!-- Search Results (temporary) -->
    <div id="prefill-results" class="results-overlay" style="display: none;">
        <div class="results-container">
            <div class="results-header">
                <h3>Zoekresultaten</h3>
                <button class="close-results" onclick="document.getElementById('prefill-results').style.display='none'">×</button>
            </div>
            <div id="prefill-results-list" class="results-list"></div>
        </div>
    </div>

    <!-- Main Content - Person Display -->
    <div id="person-display" class="person-display-container" style="display: none;">
        <!-- Person Overview Card -->
        <div class="person-overview-card">
            <div class="person-avatar">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
            <div class="person-info">
                <h1 class="person-name" id="display-person-name">-</h1>
                <div class="person-meta">
                    <span id="display-person-gender">-</span>
                    <span id="display-person-birth">-</span>
                    <span id="display-person-address">-</span>
                </div>
                <div class="person-badges">
                    <div class="badge">
                        <strong>BSN:</strong> <span id="display-person-bsn">-</span>
                    </div>
                    <div class="badge">
                        <strong>A-nummer:</strong> <span id="display-person-anummer">-</span>
                    </div>
                    <div class="badge">
                        <strong>Burgerlijke staat:</strong> <span id="display-person-status">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Details Layout -->
        <div class="details-layout">
            <!-- Categories Sidebar -->
            <div class="details-categories">
                <div class="category-item active" data-category="persoon">
                    <span class="category-number">01.</span>
                    <span class="category-name">Persoon</span>
                    <div class="category-submenu">
                        <div class="submenu-item active" data-sub="actueel">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                            </svg>
                            <span>Actueel</span>
                        </div>
                        <div class="submenu-item" data-sub="historie">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12 6 12 12 16 14"></polyline>
                            </svg>
                            <span>Historie</span>
                            <span class="badge-count" id="historie-badge" style="display: none;">0</span>
                        </div>
                    </div>
                </div>
                <div class="category-item" data-category="ouder1">
                    <span class="category-number">02.</span>
                    <span class="category-name">Ouder 1</span>
                </div>
                <div class="category-item" data-category="ouder2">
                    <span class="category-number">03.</span>
                    <span class="category-name">Ouder 2</span>
                </div>
                <div class="category-item" data-category="nationaliteit">
                    <span class="category-number">04.</span>
                    <span class="category-name">Nationaliteit</span>
                    <span class="badge-count" id="nationaliteit-badge" style="display: none;">0</span>
                </div>
                <div class="category-item" data-category="partner">
                    <span class="category-number">05.</span>
                    <span class="category-name">Huwelijk/GPS</span>
                </div>
                <div class="category-item" data-category="inschrijving">
                    <span class="category-number">07.</span>
                    <span class="category-name">Inschrijving</span>
                </div>
                <div class="category-item" data-category="verblijfplaats">
                    <span class="category-number">08.</span>
                    <span class="category-name">Verblijfplaats</span>
                </div>
                <div class="category-item" data-category="kinderen">
                    <span class="category-number">09.</span>
                    <span class="category-name">Kinderen</span>
                    <span class="badge-count" id="kinderen-badge" style="display: none;">0</span>
                </div>
            </div>

            <!-- Details Panel -->
            <div class="details-panel">
                <div class="details-header">
                    <h2 class="details-title" id="details-title">Details: 01. Persoon</h2>
                    <div class="details-toggles">
                        <label class="toggle-switch">
                            <input type="checkbox" id="toggle-descriptions">
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Toon omschrijvingen</span>
                        </label>
                        <label class="toggle-switch">
                            <input type="checkbox" id="toggle-empty">
                            <span class="toggle-slider"></span>
                            <span class="toggle-label">Toon lege elementen</span>
                        </label>
                    </div>
                </div>

                <!-- Person Details -->
                <div class="detail-section active" data-category="persoon">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">01.10 A-Nummer</div>
                            <div class="detail-value" id="detail-anummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">01.20 Burgerservicenummer</div>
                            <div class="detail-value" id="detail-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.10 Voornamen</div>
                            <div class="detail-value" id="detail-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.40 Geslachtsnaam</div>
                            <div class="detail-value" id="detail-geslachtsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.10 Geboortedatum</div>
                            <div class="detail-value" id="detail-geboortedatum">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.20 Geboorteplaats</div>
                            <div class="detail-value" id="detail-geboorteplaats">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.30 Geboorteland</div>
                            <div class="detail-value" id="detail-geboorteland">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">04.40 Geslachtsaanduiding</div>
                            <div class="detail-value" id="detail-geslachtsaanduiding">-</div>
                        </div>
                    </div>
                </div>

                <!-- Ouder 1 Details -->
                <div class="detail-section" data-category="ouder1" style="display: none;">
                    <div class="detail-section-title">Ouder 1</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">BSN</div>
                            <div class="detail-value" id="detail-ouder1-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Voornamen</div>
                            <div class="detail-value" id="detail-ouder1-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Voorvoegsel</div>
                            <div class="detail-value" id="detail-ouder1-voorvoegsel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Geslachtsnaam</div>
                            <div class="detail-value" id="detail-ouder1-geslachtsnaam">-</div>
                        </div>
                    </div>
                </div>

                <!-- Ouder 2 Details -->
                <div class="detail-section" data-category="ouder2" style="display: none;">
                    <div class="detail-section-title">Ouder 2</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">BSN</div>
                            <div class="detail-value" id="detail-ouder2-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Voornamen</div>
                            <div class="detail-value" id="detail-ouder2-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Voorvoegsel</div>
                            <div class="detail-value" id="detail-ouder2-voorvoegsel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Geslachtsnaam</div>
                            <div class="detail-value" id="detail-ouder2-geslachtsnaam">-</div>
                        </div>
                    </div>
                </div>

                <!-- Partner Details -->
                <div class="detail-section" data-category="partner" style="display: none;">
                    <div class="detail-section-title">Huwelijk/GPS</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">BSN Partner</div>
                            <div class="detail-value" id="detail-partner-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Voornamen</div>
                            <div class="detail-value" id="detail-partner-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Voorvoegsel</div>
                            <div class="detail-value" id="detail-partner-voorvoegsel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Geslachtsnaam</div>
                            <div class="detail-value" id="detail-partner-geslachtsnaam">-</div>
                        </div>
                    </div>
                </div>

                <!-- Verblijfplaats Details -->
                <div class="detail-section" data-category="verblijfplaats" style="display: none;">
                    <div class="detail-section-title">Verblijfplaats</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">Straatnaam</div>
                            <div class="detail-value" id="detail-straatnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Huisnummer</div>
                            <div class="detail-value" id="detail-huisnummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Huisnummertoevoeging</div>
                            <div class="detail-value" id="detail-huisnummertoevoeging">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Postcode</div>
                            <div class="detail-value" id="detail-postcode">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Woonplaats</div>
                            <div class="detail-value" id="detail-woonplaats">-</div>
                        </div>
                    </div>
                </div>

                <!-- Kinderen Details -->
                <div class="detail-section" data-category="kinderen" style="display: none;">
                    <div class="detail-section-title">Kinderen</div>
                    <div id="kinderen-details-list" class="kinderen-list">
                        <!-- Kinderen worden hier dynamisch toegevoegd -->
                    </div>
                </div>

                <!-- Nationaliteiten Details -->
                <div class="detail-section" data-category="nationaliteit" style="display: none;">
                    <div class="detail-section-title">Nationaliteiten</div>
                    <div id="nationaliteiten-details-list" class="nationaliteiten-list">
                        <!-- Nationaliteiten worden hier dynamisch toegevoegd -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark Theme Colors */
:root {
    --bg-primary: #1a1d24;
    --bg-secondary: #252932;
    --bg-tertiary: #2d3239;
    --bg-card: #2a2e36;
    --text-primary: #e4e6eb;
    --text-secondary: #b0b3b8;
    --text-muted: #8a8d91;
    --border-color: #3a3f47;
    --accent-blue: #1877f2;
    --accent-blue-hover: #166fe5;
    --accent-green: #42b72a;
    --error-red: #f02849;
}

/* Container */
.prefill-test-container {
    background: var(--bg-primary);
    color: var(--text-primary);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

/* Search Bar */
.search-bar {
    background: var(--bg-secondary);
    border-bottom: 1px solid var(--border-color);
    padding: 16px 32px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.search-input-wrapper {
    flex: 1;
    display: flex;
    gap: 12px;
    max-width: 600px;
}

.search-input {
    flex: 1;
    padding: 12px 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    color: var(--text-primary);
    font-size: 14px;
    transition: all 0.2s;
}

.search-input:focus {
    outline: none;
    border-color: var(--accent-blue);
    background: var(--bg-card);
}

.search-input::placeholder {
    color: var(--text-muted);
}

.search-button {
    padding: 12px 24px;
    background: var(--accent-blue);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.search-button:hover {
    background: var(--accent-blue-hover);
}

.loading-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 14px;
}

.spinner {
    width: 16px;
    height: 16px;
    border: 2px solid var(--border-color);
    border-top-color: var(--accent-blue);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.error-message {
    padding: 12px 16px;
    background: rgba(240, 40, 73, 0.1);
    border: 1px solid var(--error-red);
    border-radius: 6px;
    color: var(--error-red);
    font-size: 14px;
}

/* Results Overlay */
.results-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px;
}

.results-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    max-width: 800px;
    width: 100%;
    max-height: 80vh;
    overflow-y: auto;
    padding: 24px;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.results-header h3 {
    margin: 0;
    font-size: 20px;
    color: var(--text-primary);
}

.close-results {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 4px;
    transition: all 0.2s;
}

.close-results:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.results-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.result-item {
    padding: 16px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.result-item:hover {
    border-color: var(--accent-blue);
    background: var(--bg-secondary);
}

.result-item h4 {
    margin: 0 0 8px 0;
    color: var(--text-primary);
    font-size: 16px;
}

.result-item p {
    margin: 4px 0;
    color: var(--text-secondary);
    font-size: 13px;
}

.prefill-button {
    margin-top: 12px;
    padding: 8px 16px;
    background: var(--accent-green);
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s;
}

.prefill-button:hover {
    background: #3aa514;
}

/* Person Display Container */
.person-display-container {
    padding: 24px 32px;
    max-width: 1600px;
    margin: 0 auto;
}

/* Person Overview Card */
.person-overview-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.person-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--bg-tertiary);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    flex-shrink: 0;
}

.person-info {
    flex: 1;
}

.person-name {
    font-size: 28px;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0 0 12px 0;
}

.person-meta {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
    color: var(--text-secondary);
    font-size: 14px;
}

.person-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.badge {
    padding: 6px 12px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    font-size: 13px;
    color: var(--text-primary);
    font-weight: 500;
}

.badge strong {
    color: var(--text-secondary);
    margin-right: 4px;
}

/* Details Layout */
.details-layout {
    display: flex;
    gap: 24px;
    align-items: flex-start;
}

/* Categories Sidebar */
.details-categories {
    flex: 0 0 250px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.category-item {
    padding: 12px 16px;
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-secondary);
    font-size: 14px;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
    gap: 8px;
    position: relative;
}

.category-item:hover {
    background: var(--bg-tertiary);
}

.category-item.active {
    background: var(--bg-tertiary);
    color: var(--accent-blue);
    font-weight: 600;
}

.category-number {
    font-weight: 600;
    min-width: 32px;
}

.category-name {
    flex: 1;
}

.badge-count {
    margin-left: auto;
    padding: 2px 8px;
    background: var(--accent-blue);
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    min-width: 20px;
    text-align: center;
}

.category-submenu {
    position: absolute;
    left: 100%;
    top: 0;
    margin-left: 8px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 8px;
    min-width: 150px;
    display: none;
    z-index: 10;
}

.category-item.active .category-submenu {
    display: block;
}

.submenu-item {
    padding: 8px 12px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--text-secondary);
    font-size: 13px;
    transition: background 0.2s;
}

.submenu-item:hover {
    background: var(--bg-tertiary);
}

.submenu-item.active {
    background: var(--bg-tertiary);
    color: var(--accent-blue);
}

.submenu-item svg {
    flex-shrink: 0;
}

/* Details Panel */
.details-panel {
    flex: 1;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 24px;
    min-height: 400px;
}

.details-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid var(--border-color);
}

.details-title {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.details-toggles {
    display: flex;
    gap: 16px;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.toggle-switch input[type="checkbox"] {
    display: none;
}

.toggle-slider {
    width: 40px;
    height: 20px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    position: relative;
    transition: background 0.2s;
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    background: var(--text-muted);
    border-radius: 50%;
    top: 1px;
    left: 1px;
    transition: transform 0.2s;
}

.toggle-switch input[type="checkbox"]:checked + .toggle-slider {
    background: var(--accent-blue);
    border-color: var(--accent-blue);
}

.toggle-switch input[type="checkbox"]:checked + .toggle-slider::before {
    transform: translateX(20px);
    background: white;
}

.toggle-label {
    font-size: 13px;
    color: var(--text-secondary);
}

.detail-section {
    display: none;
}

.detail-section.active {
    display: block;
}

.detail-section-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}

.detail-grid {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 0;
}

.detail-row {
    display: contents;
}

.detail-label {
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 500;
    padding: 10px 16px 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.detail-value {
    color: var(--text-primary);
    font-size: 13px;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-color);
}

.detail-row:last-child .detail-label,
.detail-row:last-child .detail-value {
    border-bottom: none;
}

/* Kinderen & Nationaliteiten Lists */
.kinderen-list,
.nationaliteiten-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.kind-detail-item,
.nationaliteit-detail-item {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
}

.kind-detail-item h4,
.nationaliteit-detail-item h4 {
    margin: 0 0 12px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.kind-detail-grid,
.nationaliteit-detail-grid {
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 0;
}

/* Responsive */
@media (max-width: 1200px) {
    .details-layout {
        flex-direction: column;
    }
    
    .details-categories {
        flex: 1;
        width: 100%;
    }
    
    .detail-grid {
        grid-template-columns: 200px 1fr;
    }
}

@media (max-width: 768px) {
    .person-display-container {
        padding: 16px;
    }
    
    .person-overview-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .detail-label {
        padding-bottom: 4px;
        border-bottom: none;
    }
    
    .detail-value {
        padding-top: 0;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--border-color);
    }
}
</style>

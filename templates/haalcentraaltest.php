<?php
/**
 * Haal Centraal BRP Bevragen Test Pagina
 * 
 * Testpagina voor het zoeken op personen via de Haal Centraal API
 */
?>

<div id="haal-centraal-test" class="section">
    <!-- Header -->
    <div class="test-header">
        <h2>Haal Centraal BRP API Test</h2>
        <p class="settings-hint">Zoek op personen via de Haal Centraal BRP Bevragen API of Historie API 2.0</p>
    </div>
    
    <!-- Tabs Navigation -->
    <div class="tabs-container" style="display: block !important; visibility: visible !important; width: 100% !important;">
        <div class="tabs-nav" style="display: flex !important; flex-direction: row !important;">
            <button class="tab-button active" data-tab="bevragen" id="tab-bevragen" style="display: flex !important; visibility: visible !important; opacity: 1 !important;">
                <span>BRP Bevragen API</span>
                <small>Huidige gegevens</small>
            </button>
            <button class="tab-button" data-tab="historie" id="tab-historie" style="display: flex !important; visibility: visible !important; opacity: 1 !important;">
                <span>BRP Historie API 2.0</span>
                <small>Historische gegevens</small>
            </button>
        </div>
    </div>
    
    <!-- Main Layout Container -->
    <div class="main-layout-container">
        <!-- Search Section -->
        <div class="search-section-wrapper">
            <div class="search-section">
                <!-- BRP Bevragen API Tab Content -->
                <div class="tab-content active" id="tab-content-bevragen">
                    <div class="search-form-card">
                        <h3>Zoek opties</h3>
                        <div class="form-group checkbox-group">
                            <label>
                                <input type="radio" name="schema-type" id="schema-vrijbrp" value="vrijbrp" checked>
                                <span>Zoek in vrijBRP</span>
                            </label>
                            <label>
                                <input type="radio" name="schema-type" id="schema-ggm" value="ggm">
                                <span>Zoek in GGM</span>
                            </label>
                        </div>
                        <p class="schema-hint" id="schema-hint">Zoekt via Haal Centraal API in schema's die niet GGM zijn (schema ID 6 - Personen)</p>
                    </div>
                    
                    <div class="search-form-card" id="free-search-card">
                        <h3>Vrij zoeken</h3>
                        <div class="form-group">
                            <input 
                                type="text" 
                                id="free-search-input" 
                                placeholder="BSN, A-nummer, achternaam of geboortedatum" 
                                class="input-field"
                            />
                            <button id="free-search-btn" class="button primary" type="button">Zoek</button>
                        </div>
                    </div>
                    
                    <div class="search-form-card" id="bsn-search-card">
                        <h3>Zoek op BSN</h3>
                        <div class="form-group">
                            <input 
                                type="text" 
                                id="bsn-input" 
                                placeholder="bijv. 168149291" 
                                maxlength="9"
                                pattern="[0-9]{9}"
                                class="input-field"
                            />
                            <button id="search-bsn-btn" class="button primary" type="button">Zoek</button>
                        </div>
                    </div>
                    
                    <div class="search-form-card" id="list-persons-card">
                        <h3>Lijst personen</h3>
                        <div class="form-group">
                            <input 
                                type="number" 
                                id="limit-input" 
                                value="10" 
                                min="1" 
                                max="100"
                                class="input-field"
                            />
                            <button id="list-persons-btn" class="button primary" type="button">Toon</button>
                        </div>
                    </div>
                </div>
                
                <!-- BRP Historie API 2.0 Tab Content -->
                <div class="tab-content" id="tab-content-historie" style="display: none;">
                    <div class="search-form-card">
                        <h3>Verblijfplaatshistorie</h3>
                        <p class="schema-hint">Haalt alle historische verblijfplaatsen op voor een BSN volgens BRP Historie API 2.0 specificatie.</p>
                        <div class="form-group">
                            <input 
                                type="text" 
                                id="historie-bsn-input" 
                                placeholder="bijv. 168149291" 
                                maxlength="9"
                                pattern="[0-9]{9}"
                                class="input-field"
                            />
                            <button id="search-historie-btn" class="button primary" type="button">Toon Historie</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Results Section -->
        <div id="results-container" class="results-wrapper" style="display: none;">
            <div id="loading" class="loading" style="display: none;">
                <div class="spinner"></div>
                <span>Laden...</span>
            </div>
            <div id="error-message" class="error-message" style="display: none;"></div>
            <div id="results-content"></div>
        </div>
    </div>
    
    <!-- Test Formulier Section -->
    <div id="test-form-container" class="test-form-wrapper" style="display: none;">
        <div class="test-form-header">
            <h3>Test Formulier - Zaak Aanmaken</h3>
            <button id="close-form-btn" class="button secondary" type="button">Sluiten</button>
        </div>
        <form id="test-form" class="test-form">
            <div class="form-section">
                <h4>Persoonlijke gegevens</h4>
                <div class="form-row">
                    <div class="form-field">
                        <label for="form-bsn">BSN</label>
                        <input type="text" id="form-bsn" name="bsn" class="form-input" maxlength="9" pattern="[0-9]{9}" />
                    </div>
                    <div class="form-field">
                        <label for="form-voornamen">Voornamen</label>
                        <input type="text" id="form-voornamen" name="voornamen" class="form-input" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="form-voorvoegsel">Voorvoegsel</label>
                        <input type="text" id="form-voorvoegsel" name="voorvoegsel" class="form-input" />
                    </div>
                    <div class="form-field">
                        <label for="form-geslachtsnaam">Geslachtsnaam</label>
                        <input type="text" id="form-geslachtsnaam" name="geslachtsnaam" class="form-input" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="form-geboortedatum">Geboortedatum</label>
                        <input type="date" id="form-geboortedatum" name="geboortedatum" class="form-input" />
                    </div>
                    <div class="form-field">
                        <label for="form-geboorteplaats">Geboorteplaats</label>
                        <input type="text" id="form-geboorteplaats" name="geboorteplaats" class="form-input" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="form-geslacht">Geslacht</label>
                        <select id="form-geslacht" name="geslacht" class="form-input">
                            <option value="">-- Selecteer --</option>
                            <option value="man">Man</option>
                            <option value="vrouw">Vrouw</option>
                            <option value="onbekend">Onbekend</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="form-anummer">A-nummer</label>
                        <input type="text" id="form-anummer" name="anummer" class="form-input" />
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <h4>Adresgegevens</h4>
                <div class="form-row">
                    <div class="form-field">
                        <label for="form-straatnaam">Straatnaam</label>
                        <input type="text" id="form-straatnaam" name="straatnaam" class="form-input" />
                    </div>
                    <div class="form-field">
                        <label for="form-huisnummer">Huisnummer</label>
                        <input type="text" id="form-huisnummer" name="huisnummer" class="form-input" />
                    </div>
                    <div class="form-field">
                        <label for="form-huisnummertoevoeging">Toevoeging</label>
                        <input type="text" id="form-huisnummertoevoeging" name="huisnummertoevoeging" class="form-input" />
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="form-postcode">Postcode</label>
                        <input type="text" id="form-postcode" name="postcode" class="form-input" />
                    </div>
                    <div class="form-field">
                        <label for="form-woonplaats">Woonplaats</label>
                        <input type="text" id="form-woonplaats" name="woonplaats" class="form-input" />
                    </div>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" id="clear-form-btn" class="button secondary">Wissen</button>
                <button type="submit" class="button primary">Zaak aanmaken</button>
            </div>
        </form>
    </div>
</div>

<style nonce="<?php p($_['cspNonce'] ?? '') ?>">
/* Ensure body and html allow scrolling */
html, body, #content, #app-content {
    overflow-y: auto !important;
    overflow-x: hidden !important;
    height: auto !important;
    max-height: none !important;
}

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
    --success-green: #42b72a;
}

#haal-centraal-test {
    background: var(--bg-primary);
    color: var(--text-primary);
    min-height: 100vh;
    padding: 0;
    margin: 0;
    position: relative;
    overflow-y: visible !important;
    overflow-x: hidden !important;
    height: auto !important;
}

.test-header {
    padding: 24px 32px;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
}

.test-header h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 600;
    color: var(--text-primary);
}

.settings-hint {
    margin: 0;
    color: var(--text-secondary);
    font-size: 14px;
}

/* Main Layout */
.main-layout-container {
    display: flex;
    gap: 24px;
    align-items: flex-start;
    padding: 24px 32px;
    padding-bottom: 50px;
    min-height: auto;
    overflow-y: visible !important;
    overflow-x: hidden !important;
}

/* Search Section */
.search-section-wrapper {
    flex: 0 0 400px;
    position: sticky;
    top: 20px;
    max-height: calc(100vh - 100px);
    overflow-y: auto;
    overflow-x: hidden;
}

.search-section {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.search-form-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
}

.search-form-card h3 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
}

.form-group {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.input-field {
    flex: 1;
    padding: 10px 14px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-primary);
    font-size: 14px;
    transition: border-color 0.2s;
}

.input-field:focus {
    outline: none;
    border-color: var(--accent-blue);
}

.input-field::placeholder {
    color: var(--text-muted);
}

.button {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
    white-space: nowrap;
}

.button.primary {
    background-color: var(--accent-blue);
    color: white;
}

.button.primary:hover {
    background-color: var(--accent-blue-hover);
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: var(--text-primary);
}

.checkbox-group input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

/* Tabs Navigation */
.tabs-container {
    background: var(--bg-secondary) !important;
    border-bottom: 1px solid var(--border-color) !important;
    padding: 0 32px !important;
    position: relative !important;
    z-index: 10 !important;
    width: 100% !important;
    display: block !important;
    visibility: visible !important;
}

.tabs-nav {
    display: flex !important;
    gap: 0 !important;
    border-bottom: 2px solid var(--border-color) !important;
    width: 100% !important;
    flex-direction: row !important;
}

.tab-button {
    background: transparent !important;
    border: none !important;
    border-bottom: 3px solid transparent !important;
    padding: 16px 24px !important;
    cursor: pointer !important;
    color: var(--text-secondary) !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    transition: all 0.2s ease !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: flex-start !important;
    gap: 4px !important;
    min-width: 200px !important;
    visibility: visible !important;
    opacity: 1 !important;
    position: relative !important;
}

.tab-button:hover {
    background: var(--bg-tertiary);
    color: var(--text-primary);
}

.tab-button.active {
    color: var(--accent-blue);
    border-bottom-color: var(--accent-blue);
    background: var(--bg-primary);
}

.tab-button span {
    font-size: 15px;
    font-weight: 600;
}

.tab-button small {
    font-size: 12px;
    color: var(--text-muted);
    font-weight: 400;
}

.tab-button.active small {
    color: var(--text-secondary);
}

.tab-content {
    display: none;
    flex-direction: column;
    gap: 16px;
}

.tab-content.active {
    display: flex !important;
}

.schema-hint {
    margin: 12px 0 0 0;
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.4;
}

/* Results Section */
.results-wrapper {
    flex: 1;
    min-width: 0;
    background: var(--bg-primary);
    padding: 24px;
    border-radius: 8px;
    overflow-x: hidden;
}

/* Person Header */
.person-header {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
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
    font-size: 36px;
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
    width: 100%;
    min-height: auto;
    overflow: visible;
}

.details-categories {
    flex: 0 0 250px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
    max-height: none !important;
    overflow-y: visible !important;
    overflow-x: visible !important;
    min-height: auto;
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
    margin-bottom: 0;
    transition: background-color 0.2s;
    display: block !important;
    width: 100%;
    text-align: left;
    white-space: nowrap;
    box-sizing: border-box;
    visibility: visible !important;
    opacity: 1 !important;
}

.category-item:hover {
    background: var(--bg-tertiary);
}

.category-item.active {
    background: var(--accent-blue);
    color: white;
    font-weight: 600;
}

.category-subitem {
    padding: 8px 16px 8px 32px;
    font-size: 13px;
    color: var(--text-muted);
}

.category-subitem.active {
    color: var(--text-primary);
    font-weight: 500;
}

.details-content {
    flex: 1;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 24px;
    max-height: none !important;
    overflow-y: visible !important;
    overflow-x: hidden;
    min-height: auto;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
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
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0;
}

.details-toggles {
    display: flex;
    gap: 16px;
    align-items: center;
}

.toggle-switch {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: var(--text-secondary);
}

.toggle-switch input[type="checkbox"] {
    width: 36px;
    height: 20px;
    appearance: none;
    background: var(--bg-tertiary);
    border-radius: 10px;
    position: relative;
    cursor: pointer;
    transition: background-color 0.2s;
}

.toggle-switch input[type="checkbox"]:checked {
    background: var(--accent-blue);
}

.toggle-switch input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: white;
    top: 2px;
    left: 2px;
    transition: transform 0.2s;
}

.toggle-switch input[type="checkbox"]:checked::before {
    transform: translateX(16px);
}

.details-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 16px 24px;
}

.detail-row {
    display: contents;
}

.detail-label {
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 500;
    padding: 8px 0;
}

.detail-value {
    color: var(--text-primary);
    font-size: 13px;
    padding: 8px 0;
}

.detail-section {
    margin-bottom: 32px;
}

.detail-section-title {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 16px 0;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}

/* Loading & Error */
.loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: var(--text-secondary);
    gap: 16px;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid var(--border-color);
    border-top-color: var(--accent-blue);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.error-message {
    padding: 16px 20px;
    background: rgba(240, 40, 73, 0.1);
    border: 1px solid var(--error-red);
    border-radius: 6px;
    color: var(--error-red);
    margin-bottom: 24px;
}

/* Person List */
.person-list {
    display: grid;
    gap: 16px;
}

.person-list-item {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.person-list-item:hover {
    border-color: var(--accent-blue);
    background: var(--bg-tertiary);
}

.person-list-item h4 {
    margin: 0 0 8px 0;
    color: var(--text-primary);
    font-size: 16px;
}

.person-list-item p {
    margin: 4px 0;
    color: var(--text-secondary);
    font-size: 13px;
}

.view-details-btn {
    margin-top: 12px;
    padding: 8px 16px;
    background: var(--accent-blue);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
}

.view-details-btn:hover {
    background: var(--accent-blue-hover);
}

.prefill-btn {
    margin-top: 12px;
    padding: 8px 16px;
    background: var(--accent-green);
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    margin-right: 8px;
}

.prefill-btn:hover {
    background: #3aa514;
}

.pagination-info {
    margin-top: 24px;
    padding: 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    text-align: center;
    color: var(--text-secondary);
    font-size: 13px;
}

/* Test Formulier Styles */
.test-form-wrapper {
    margin: 32px;
    background: var(--bg-card);
    border: 2px solid var(--accent-blue);
    border-radius: 8px;
    overflow-y: auto;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    display: none; /* Standaard verborgen */
}

.test-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 24px;
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-secondary);
    position: sticky;
    top: 0;
    z-index: 10;
}

.test-form-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
}

.button.secondary {
    background-color: var(--bg-tertiary);
    color: var(--text-primary);
    border: 1px solid var(--border-color);
}

.button.secondary:hover {
    background-color: var(--border-color);
}

.test-form {
    padding: 24px;
}

.form-section {
    margin-bottom: 32px;
}

.form-section h4 {
    margin: 0 0 16px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--text-primary);
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-color);
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-field label {
    margin-bottom: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--text-secondary);
}

.form-input {
    padding: 10px 14px;
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 6px;
    color: var(--text-primary);
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent-blue);
}

.form-input::placeholder {
    color: var(--text-muted);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding-top: 24px;
    border-top: 1px solid var(--border-color);
    margin-top: 24px;
}

/* Responsive */
@media (max-width: 1024px) {
    .details-layout {
        flex-direction: column;
    }
    
    .details-categories {
        flex: none;
        width: 100%;
    }
    
    .search-section {
        max-width: 100%;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .test-form-wrapper {
        max-height: 90vh;
    }
}
</style>

<script nonce="<?php p($_['cspNonce'] ?? '') ?>" type="text/javascript">
(function() {
    'use strict';
    
    var API_BASE = '';
    var initialized = false;
    
    function showLoading() {
        var el = document.getElementById('loading');
        if (el) el.style.display = 'flex';
    }

    function hideLoading() {
        var el = document.getElementById('loading');
        if (el) el.style.display = 'none';
    }

    function showError(message) {
        var errorEl = document.getElementById('error-message');
        var resultsContainer = document.getElementById('results-container');
        if (errorEl) {
            errorEl.innerHTML = escapeHtml(message);
            errorEl.style.display = 'block';
        }
        if (resultsContainer) resultsContainer.style.display = 'block';
    }

    function hideError() {
        var errorEl = document.getElementById('error-message');
        if (errorEl) errorEl.style.display = 'none';
    }

    function hideResults() {
        var resultsContainer = document.getElementById('results-container');
        if (resultsContainer) resultsContainer.style.display = 'none';
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        // Format YYYY-MM-DD to DD-MM-YYYY
        if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
            var parts = dateStr.split('-');
            return parts[2] + '-' + parts[1] + '-' + parts[0];
        }
        return dateStr;
    }

    function calculateAge(birthDate) {
        if (!birthDate) return '';
        var today = new Date();
        var birth = new Date(birthDate);
        var age = today.getFullYear() - birth.getFullYear();
        var monthDiff = today.getMonth() - birth.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
            age--;
        }
        return age + ' jaar';
    }

    function displayPerson(person) {
        var resultsContainer = document.getElementById('results-container');
        var resultsContent = document.getElementById('results-content');
        
        if (!resultsContainer || !resultsContent) return;
        
        resultsContainer.style.display = 'block';
        hideError();
        
        var naam = person.naam || {};
        var geboorte = person.geboorte || {};
        var verblijfplaats = person.verblijfplaats || {};
        
        var voornamen = naam.voornamen ? naam.voornamen.join(' ') : '';
        var geslachtsnaam = naam.geslachtsnaam || '';
        var voorvoegsel = naam.voorvoegsel || '';
        var volledigeNaam = voorvoegsel ? geslachtsnaam + ', ' + voornamen + ' ' + voorvoegsel : geslachtsnaam + ', ' + voornamen;
        
        var geboortedatum = geboorte.datum ? formatDate(geboorte.datum.datum) : '';
        var leeftijd = geboortedatum ? calculateAge(geboorte.datum.datum) : '';
        var geboorteplaats = geboorte.plaats || '';
        
        var geslacht = person.geslacht === 'man' ? 'Man' : person.geslacht === 'vrouw' ? 'Vrouw' : '';
        
        var adres = '';
        if (verblijfplaats.straatnaam) {
            adres = verblijfplaats.straatnaam;
            if (verblijfplaats.huisnummer) {
                adres += ' ' + verblijfplaats.huisnummer;
                if (verblijfplaats.huisnummertoevoeging) {
                    adres += verblijfplaats.huisnummertoevoeging;
                }
            }
            if (verblijfplaats.postcode || verblijfplaats.woonplaatsnaam) {
                adres += ', ';
                if (verblijfplaats.postcode) adres += verblijfplaats.postcode;
                if (verblijfplaats.postcode && verblijfplaats.woonplaatsnaam) adres += ' ';
                if (verblijfplaats.woonplaatsnaam) adres += '(' + verblijfplaats.woonplaatsnaam + ')';
            }
        }
        
        var html = '<div class="person-header">';
        html += '<div class="person-avatar"></div>';
        html += '<div class="person-info">';
        html += '<h3 class="person-name">' + escapeHtml(volledigeNaam || 'Onbekend') + '</h3>';
        html += '<div class="person-meta">';
        if (geslacht) html += '<div>' + geslacht + '</div>';
        if (geboortedatum) html += '<div>' + geboortedatum + (leeftijd ? ' (' + leeftijd + ')' : '') + '</div>';
        if (adres) html += '<div>' + escapeHtml(adres) + '</div>';
        html += '</div>';
        html += '<div class="person-badges">';
        if (person.burgerservicenummer) {
            html += '<div class="badge"><strong>BSN:</strong> ' + escapeHtml(person.burgerservicenummer) + '</div>';
        }
        if (person.administratienummer) {
            html += '<div class="badge"><strong>A-nummer:</strong> ' + escapeHtml(person.administratienummer) + '</div>';
        }
        if (person.burgerlijkeStaat) {
            html += '<div class="badge"><strong>Burgerlijke staat:</strong> ' + escapeHtml(person.burgerlijkeStaat) + '</div>';
        }
        html += '</div>';
        html += '</div></div>';
        
        html += '<div class="details-layout">';
        html += '<div class="details-categories">';
        html += '<div class="category-item active" data-tab="persoon">01. Persoon</div>';
        html += '<div class="category-item" data-tab="nationaliteiten">04. Nationaliteiten</div>';
        html += '<div class="category-item" data-tab="partners">05. Partners</div>';
        html += '<div class="category-item" data-tab="verblijfplaats">08. Verblijfplaats</div>';
        html += '<div class="category-item" data-tab="kinderen">09. Kinderen</div>';
        html += '<div class="category-item" data-tab="ouders">09. Ouders</div>';
        html += '</div>';
        
        html += '<div class="details-content">';
        html += '<div class="details-header">';
        html += '<h4 class="details-title" id="details-title">Details: 01. Persoon</h4>';
        html += '<div class="details-toggles">';
        html += '<label class="toggle-switch"><input type="checkbox"><span>Toon omschrijvingen</span></label>';
        html += '<label class="toggle-switch"><input type="checkbox"><span>Toon lege elementen</span></label>';
        html += '</div>';
        html += '</div>';
        
        // Tab content containers
        html += '<div class="tab-content active" data-tab-content="persoon">';
        html += '<div class="details-grid">';
        
        if (person.administratienummer) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">01.10 A-Nummer</div>';
            html += '<div class="detail-value">' + escapeHtml(person.administratienummer) + '</div>';
            html += '</div>';
        }
        
        if (person.burgerservicenummer) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">01.20 Burgerservicenummer</div>';
            html += '<div class="detail-value">' + escapeHtml(person.burgerservicenummer) + '</div>';
            html += '</div>';
        }
        
        if (voornamen) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">02.10 Voornamen</div>';
            html += '<div class="detail-value">' + escapeHtml(voornamen) + '</div>';
            html += '</div>';
        }
        
        if (voorvoegsel) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">02.30 Voorvoegsel Geslachtsnaam</div>';
            html += '<div class="detail-value">' + escapeHtml(voorvoegsel) + '</div>';
            html += '</div>';
        }
        
        if (geslachtsnaam) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">02.40 Geslachtsnaam</div>';
            html += '<div class="detail-value">' + escapeHtml(geslachtsnaam) + '</div>';
            html += '</div>';
        }
        
        if (geboortedatum) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">03.10 Geboortedatum</div>';
            html += '<div class="detail-value">' + escapeHtml(geboortedatum) + '</div>';
            html += '</div>';
        }
        
        if (geboorteplaats) {
            html += '<div class="detail-row">';
            html += '<div class="detail-label">03.20 Geboorteplaats</div>';
            html += '<div class="detail-value">' + escapeHtml(geboorteplaats) + '</div>';
            html += '</div>';
        }
        
        html += '</div>'; // Close details-grid
        html += '</div>'; // Close tab-content persoon
        
        // Tab content voor andere tabs (wordt later ingevuld via loadRelaties)
        html += '<div class="tab-content" data-tab-content="nationaliteiten">';
        html += '<div class="details-grid" id="nationaliteiten-content">';
        html += '<div style="padding: 20px; color: var(--text-secondary);">Relaties worden geladen...</div>';
        html += '</div></div>';
        
        html += '<div class="tab-content" data-tab-content="partners">';
        html += '<div class="details-grid" id="partners-content">';
        html += '<div style="padding: 20px; color: var(--text-secondary);">Relaties worden geladen...</div>';
        html += '</div></div>';
        
        html += '<div class="tab-content" data-tab-content="verblijfplaats">';
        html += '<div class="details-grid" id="verblijfplaats-content">';
        if (verblijfplaats.straatnaam) {
            if (verblijfplaats.straatnaam) {
                html += '<div class="detail-row">';
                html += '<div class="detail-label">Straatnaam</div>';
                html += '<div class="detail-value">' + escapeHtml(verblijfplaats.straatnaam) + '</div>';
                html += '</div>';
            }
            if (verblijfplaats.huisnummer) {
                html += '<div class="detail-row">';
                html += '<div class="detail-label">Huisnummer</div>';
                html += '<div class="detail-value">' + escapeHtml(verblijfplaats.huisnummer + (verblijfplaats.huisnummertoevoeging || '')) + '</div>';
                html += '</div>';
            }
            if (verblijfplaats.postcode) {
                html += '<div class="detail-row">';
                html += '<div class="detail-label">Postcode</div>';
                html += '<div class="detail-value">' + escapeHtml(verblijfplaats.postcode) + '</div>';
                html += '</div>';
            }
            if (verblijfplaats.woonplaatsnaam) {
                html += '<div class="detail-row">';
                html += '<div class="detail-label">Woonplaats</div>';
                html += '<div class="detail-value">' + escapeHtml(verblijfplaats.woonplaatsnaam) + '</div>';
                html += '</div>';
            }
        } else {
            html += '<div style="padding: 20px; color: var(--text-secondary);">Geen verblijfplaats beschikbaar</div>';
        }
        html += '</div></div>';
        
        html += '<div class="tab-content" data-tab-content="kinderen">';
        html += '<div class="details-grid" id="kinderen-content">';
        html += '<div style="padding: 20px; color: var(--text-secondary);">Relaties worden geladen...</div>';
        html += '</div></div>';
        
        html += '<div class="tab-content" data-tab-content="ouders">';
        html += '<div class="details-grid" id="ouders-content">';
        html += '<div style="padding: 20px; color: var(--text-secondary);">Relaties worden geladen...</div>';
        html += '</div></div>';
        
        html += '</div></div>'; // Close details-content and details-layout
        
        // Voeg prefill knop toe
        html += '<div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid var(--border-color);">';
        html += '<button class="prefill-btn" data-person=\'' + JSON.stringify(person).replace(/'/g, "&#39;") + '\' style="font-size: 14px; padding: 12px 24px;">Prefill formulier met deze persoon</button>';
        html += '</div>';
        
        console.log('HTML gegenereerd, lengte:', html.length);
        console.log('Tabs in HTML:', html.includes('04. Nationaliteiten'), html.includes('05. Partners'), html.includes('09. Kinderen'));
        
        resultsContent.innerHTML = html;
        
        // Debug: Check of tabs zijn aangemaakt
        setTimeout(function() {
            var allTabs = resultsContent.querySelectorAll('.category-item');
            console.log('=== DEBUG TABS ===');
            console.log('Aantal tabs gevonden:', allTabs.length);
            allTabs.forEach(function(tab, index) {
                console.log('Tab ' + index + ':', tab.textContent.trim(), 'Zichtbaar:', tab.offsetHeight > 0, 'Display:', window.getComputedStyle(tab).display);
            });
            console.log('==================');
        }, 200);
        resultsContainer.style.display = 'block';
        
        // Tab switching functionaliteit
        var tabItems = resultsContent.querySelectorAll('.category-item');
        var tabContents = resultsContent.querySelectorAll('.tab-content');
        var detailsTitle = resultsContent.querySelector('#details-title');
        
        tabItems.forEach(function(item) {
            item.addEventListener('click', function() {
                var tabName = this.getAttribute('data-tab');
                
                // Update active states
                tabItems.forEach(function(t) { t.classList.remove('active'); });
                this.classList.add('active');
                
                // Update content visibility
                tabContents.forEach(function(content) {
                    content.classList.remove('active');
                });
                
                var targetContent = resultsContent.querySelector('[data-tab-content="' + tabName + '"]');
                if (targetContent) {
                    targetContent.classList.add('active');
                }
                
                // Update title
                if (detailsTitle) {
                    var titles = {
                        'persoon': 'Details: 01. Persoon',
                        'nationaliteiten': 'Details: 04. Nationaliteiten',
                        'partners': 'Details: 05. Partners',
                        'verblijfplaats': 'Details: 08. Verblijfplaats',
                        'kinderen': 'Details: 09. Kinderen',
                        'ouders': 'Details: 09. Ouders'
                    };
                    detailsTitle.textContent = titles[tabName] || 'Details';
                }
            });
        });
        
        // Event listener voor prefill knop
        var prefillBtn = resultsContent.querySelector('.prefill-btn');
        if (prefillBtn) {
            prefillBtn.addEventListener('click', function() {
                prefillForm(person);
            });
        }
        
        // Haal relaties op als BSN beschikbaar is
        if (person.burgerservicenummer) {
            setTimeout(function() {
                console.log('Aanroepen loadRelaties voor BSN:', person.burgerservicenummer);
                loadRelaties(person.burgerservicenummer);
            }, 100);
        }
    }
    
    /**
     * Haal alle relaties op voor een BSN (partners, kinderen, ouders, verblijfplaats, nationaliteiten)
     */
    function loadRelaties(bsn) {
        if (!bsn) {
            console.log('loadRelaties: Geen BSN opgegeven');
            return;
        }
        
        console.log('loadRelaties: Start ophalen relaties voor BSN:', bsn);
        
        // Vind tab content containers
        var partnersContent = document.getElementById('partners-content');
        var kinderenContent = document.getElementById('kinderen-content');
        var oudersContent = document.getElementById('ouders-content');
        var nationaliteitenContent = document.getElementById('nationaliteiten-content');
        var verblijfplaatsContent = document.getElementById('verblijfplaats-content');
        
        if (!partnersContent || !kinderenContent || !oudersContent || !nationaliteitenContent) {
            console.log('loadRelaties: Tab containers niet gevonden, retry over 200ms...');
            setTimeout(function() {
                loadRelaties(bsn);
            }, 200);
            return;
        }
        
        // Toon loading indicators
        if (partnersContent) partnersContent.innerHTML = '<div style="padding: 16px; color: var(--text-secondary);">Partners ophalen...</div>';
        if (kinderenContent) kinderenContent.innerHTML = '<div style="padding: 16px; color: var(--text-secondary);">Kinderen ophalen...</div>';
        if (oudersContent) oudersContent.innerHTML = '<div style="padding: 16px; color: var(--text-secondary);">Ouders ophalen...</div>';
        if (nationaliteitenContent) nationaliteitenContent.innerHTML = '<div style="padding: 16px; color: var(--text-secondary);">Nationaliteiten ophalen...</div>';
        
        var schemaType = getSchemaType();
        // Voor vrijBRP: geen ggm parameter (of expliciet ggm=false)
        // Voor GGM: ggm=true parameter
        var ggmParam = schemaType === 'ggm' ? '?ggm=true' : '?ggm=false';
        var baseUrl = API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn);
        
        console.log('loadRelaties: baseUrl:', baseUrl);
        console.log('loadRelaties: schemaType:', schemaType);
        console.log('loadRelaties: ggmParam:', ggmParam);
        
        // Haal alle relaties parallel op
        Promise.all([
            fetch(baseUrl + '/partners' + ggmParam, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(r => {
                console.log('Partners response status:', r.status, r.statusText);
                return r.ok ? r.json() : { _embedded: { partners: [] } };
            }).catch(err => {
                console.error('Partners fetch error:', err);
                return { _embedded: { partners: [] } };
            }),
            
            fetch(baseUrl + '/kinderen' + ggmParam, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(r => {
                console.log('Kinderen response status:', r.status, r.statusText);
                return r.ok ? r.json() : { _embedded: { kinderen: [] } };
            }).catch(err => {
                console.error('Kinderen fetch error:', err);
                return { _embedded: { kinderen: [] } };
            }),
            
            fetch(baseUrl + '/ouders' + ggmParam, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(r => {
                console.log('Ouders response status:', r.status, r.statusText);
                return r.ok ? r.json() : { _embedded: { ouders: [] } };
            }).catch(err => {
                console.error('Ouders fetch error:', err);
                return { _embedded: { ouders: [] } };
            }),
            
            fetch(baseUrl + '/verblijfplaats' + ggmParam, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(r => {
                console.log('Verblijfplaats response status:', r.status, r.statusText);
                return r.ok ? r.json() : null;
            }).catch(err => {
                console.error('Verblijfplaats fetch error:', err);
                return null;
            }),
            
            fetch(baseUrl + '/nationaliteiten' + ggmParam, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(r => {
                console.log('Nationaliteiten response status:', r.status, r.statusText);
                return r.ok ? r.json() : { _embedded: { nationaliteiten: [] } };
            }).catch(err => {
                console.error('Nationaliteiten fetch error:', err);
                return { _embedded: { nationaliteiten: [] } };
            })
        ]).then(function(results) {
            console.log('loadRelaties: Alle responses ontvangen:', results);
            var partnersData = results[0];
            var kinderenData = results[1];
            var oudersData = results[2];
            var verblijfplaatsData = results[3];
            var nationaliteitenData = results[4];
            
            // Partners
            var partners = partnersData._embedded && partnersData._embedded.partners ? partnersData._embedded.partners : [];
            var partnersHtml = '';
            if (partners.length > 0) {
                partners.forEach(function(partner) {
                    var partnerNaam = partner.naam || {};
                    var partnerVoornamen = partnerNaam.voornamen ? partnerNaam.voornamen.join(' ') : '';
                    var partnerGeslachtsnaam = partnerNaam.geslachtsnaam || '';
                    var partnerVoorvoegsel = partnerNaam.voorvoegsel || '';
                    var partnerVolledigeNaam = partnerVoorvoegsel ? partnerGeslachtsnaam + ', ' + partnerVoornamen + ' ' + partnerVoorvoegsel : partnerGeslachtsnaam + ', ' + partnerVoornamen;
                    
                    partnersHtml += '<div class="detail-row">';
                    partnersHtml += '<div class="detail-label">Partner</div>';
                    partnersHtml += '<div class="detail-value">';
                    partnersHtml += escapeHtml(partnerVolledigeNaam || 'Onbekend');
                    if (partner.burgerservicenummer) {
                        partnersHtml += ' <span style="color: var(--text-muted);">(BSN: ' + escapeHtml(partner.burgerservicenummer) + ')</span>';
                    }
                    partnersHtml += '</div>';
                    partnersHtml += '</div>';
                });
            } else {
                partnersHtml = '<div style="padding: 20px; color: var(--text-muted);">Geen partners gevonden.</div>';
            }
            if (partnersContent) partnersContent.innerHTML = partnersHtml;
            
            // Kinderen
            var kinderen = kinderenData._embedded && kinderenData._embedded.kinderen ? kinderenData._embedded.kinderen : [];
            var kinderenHtml = '';
            if (kinderen.length > 0) {
                kinderen.forEach(function(kind) {
                    var kindNaam = kind.naam || {};
                    var kindVoornamen = kindNaam.voornamen ? kindNaam.voornamen.join(' ') : '';
                    var kindGeslachtsnaam = kindNaam.geslachtsnaam || '';
                    var kindVoorvoegsel = kindNaam.voorvoegsel || '';
                    var kindVolledigeNaam = kindVoorvoegsel ? kindGeslachtsnaam + ', ' + kindVoornamen + ' ' + kindVoorvoegsel : kindGeslachtsnaam + ', ' + kindVoornamen;
                    
                    var kindGeboorte = kind.geboorte || {};
                    var kindGeboortedatum = kindGeboorte.datum ? formatDate(kindGeboorte.datum.datum) : '';
                    
                    kinderenHtml += '<div class="detail-row">';
                    kinderenHtml += '<div class="detail-label">Kind</div>';
                    kinderenHtml += '<div class="detail-value">';
                    kinderenHtml += escapeHtml(kindVolledigeNaam || 'Onbekend');
                    if (kindGeboortedatum) {
                        kinderenHtml += ' <span style="color: var(--text-muted);">(' + escapeHtml(kindGeboortedatum) + ')</span>';
                    }
                    if (kind.burgerservicenummer) {
                        kinderenHtml += ' <span style="color: var(--text-muted);">(BSN: ' + escapeHtml(kind.burgerservicenummer) + ')</span>';
                    }
                    kinderenHtml += '</div>';
                    kinderenHtml += '</div>';
                });
            } else {
                kinderenHtml = '<div style="padding: 20px; color: var(--text-muted);">Geen kinderen gevonden.</div>';
            }
            if (kinderenContent) kinderenContent.innerHTML = kinderenHtml;
            
            // Ouders
            var ouders = oudersData._embedded && oudersData._embedded.ouders ? oudersData._embedded.ouders : [];
            var oudersHtml = '';
            if (ouders.length > 0) {
                ouders.forEach(function(ouder, index) {
                    var ouderNaam = ouder.naam || {};
                    var ouderVoornamen = ouderNaam.voornamen ? ouderNaam.voornamen.join(' ') : '';
                    var ouderGeslachtsnaam = ouderNaam.geslachtsnaam || '';
                    var ouderVoorvoegsel = ouderNaam.voorvoegsel || '';
                    var ouderVolledigeNaam = ouderVoorvoegsel ? ouderGeslachtsnaam + ', ' + ouderVoornamen + ' ' + ouderVoorvoegsel : ouderGeslachtsnaam + ', ' + ouderVoornamen;
                    
                    oudersHtml += '<div class="detail-row">';
                    oudersHtml += '<div class="detail-label">Ouder ' + (index + 1) + '</div>';
                    oudersHtml += '<div class="detail-value">';
                    oudersHtml += escapeHtml(ouderVolledigeNaam || 'Onbekend');
                    if (ouder.burgerservicenummer) {
                        oudersHtml += ' <span style="color: var(--text-muted);">(BSN: ' + escapeHtml(ouder.burgerservicenummer) + ')</span>';
                    }
                    oudersHtml += '</div>';
                    oudersHtml += '</div>';
                });
            } else {
                oudersHtml = '<div style="padding: 20px; color: var(--text-muted);">Geen ouders gevonden.</div>';
            }
            if (oudersContent) oudersContent.innerHTML = oudersHtml;
            
            // Verblijfplaats (update als er nieuwe data is)
            if (verblijfplaatsData && verblijfplaatsData.straatnaam && verblijfplaatsContent) {
                var verblijfplaatsHtml = '';
                if (verblijfplaatsData.straatnaam) {
                    verblijfplaatsHtml += '<div class="detail-row">';
                    verblijfplaatsHtml += '<div class="detail-label">Straatnaam</div>';
                    verblijfplaatsHtml += '<div class="detail-value">' + escapeHtml(verblijfplaatsData.straatnaam) + '</div>';
                    verblijfplaatsHtml += '</div>';
                }
                if (verblijfplaatsData.huisnummer) {
                    verblijfplaatsHtml += '<div class="detail-row">';
                    verblijfplaatsHtml += '<div class="detail-label">Huisnummer</div>';
                    verblijfplaatsHtml += '<div class="detail-value">' + escapeHtml(verblijfplaatsData.huisnummer + (verblijfplaatsData.huisnummertoevoeging || '')) + '</div>';
                    verblijfplaatsHtml += '</div>';
                }
                if (verblijfplaatsData.postcode) {
                    verblijfplaatsHtml += '<div class="detail-row">';
                    verblijfplaatsHtml += '<div class="detail-label">Postcode</div>';
                    verblijfplaatsHtml += '<div class="detail-value">' + escapeHtml(verblijfplaatsData.postcode) + '</div>';
                    verblijfplaatsHtml += '</div>';
                }
                if (verblijfplaatsData.woonplaatsnaam) {
                    verblijfplaatsHtml += '<div class="detail-row">';
                    verblijfplaatsHtml += '<div class="detail-label">Woonplaats</div>';
                    verblijfplaatsHtml += '<div class="detail-value">' + escapeHtml(verblijfplaatsData.woonplaatsnaam) + '</div>';
                    verblijfplaatsHtml += '</div>';
                }
                verblijfplaatsContent.innerHTML = verblijfplaatsHtml;
            }
            
            // Nationaliteiten
            var nationaliteiten = nationaliteitenData._embedded && nationaliteitenData._embedded.nationaliteiten ? nationaliteitenData._embedded.nationaliteiten : [];
            var nationaliteitenHtml = '';
            if (nationaliteiten.length > 0) {
                nationaliteiten.forEach(function(nat) {
                    var natInfo = nat.nationaliteit || {};
                    nationaliteitenHtml += '<div class="detail-row">';
                    nationaliteitenHtml += '<div class="detail-label">Nationaliteit</div>';
                    nationaliteitenHtml += '<div class="detail-value">';
                    if (natInfo.omschrijving) {
                        nationaliteitenHtml += escapeHtml(natInfo.omschrijving);
                    }
                    if (natInfo.code) {
                        nationaliteitenHtml += ' <span style="color: var(--text-muted);">(code: ' + escapeHtml(natInfo.code) + ')</span>';
                    }
                    nationaliteitenHtml += '</div>';
                    nationaliteitenHtml += '</div>';
                });
            } else {
                nationaliteitenHtml = '<div style="padding: 20px; color: var(--text-muted);">Geen nationaliteiten gevonden.</div>';
            }
            if (nationaliteitenContent) nationaliteitenContent.innerHTML = nationaliteitenHtml;
            
            console.log('loadRelaties: Partners:', partners.length, 'Kinderen:', kinderen.length, 'Ouders:', ouders.length, 'Nationaliteiten:', nationaliteiten.length);
        }).catch(function(error) {
            console.error('loadRelaties: Fout bij ophalen relaties:', error);
            console.error('loadRelaties: Error stack:', error.stack);
            var errorMsg = '<div style="padding: 16px; color: var(--error-red);">Fout bij ophalen relaties: ' + escapeHtml(error.message || error) + '</div>';
            if (partnersContent) partnersContent.innerHTML = errorMsg;
            if (kinderenContent) kinderenContent.innerHTML = errorMsg;
            if (oudersContent) oudersContent.innerHTML = errorMsg;
            if (nationaliteitenContent) nationaliteitenContent.innerHTML = errorMsg;
        });
    }

    function displayPersonList(data) {
        var resultsContainer = document.getElementById('results-container');
        var resultsContent = document.getElementById('results-content');
        
        if (!resultsContainer || !resultsContent) return;
        
        resultsContainer.style.display = 'block';
        hideError();
        
        var persons = data._embedded && data._embedded.ingeschrevenpersonen ? data._embedded.ingeschrevenpersonen : [];
        var pagination = data.page || {};
        
        var html = '<div class="person-list">';
        
        persons.forEach(function(person) {
            var naam = person.naam || {};
            var voornamen = naam.voornamen ? naam.voornamen.join(' ') : '';
            var geslachtsnaam = naam.geslachtsnaam || '';
            var voorvoegsel = naam.voorvoegsel || '';
            var volledigeNaam = voorvoegsel ? geslachtsnaam + ', ' + voornamen + ' ' + voorvoegsel : geslachtsnaam + ', ' + voornamen;
            
            html += '<div class="person-list-item" data-bsn="' + escapeHtml(person.burgerservicenummer || '') + '">';
            html += '<h4>' + escapeHtml(volledigeNaam || 'Onbekend') + '</h4>';
            if (person.burgerservicenummer) {
                html += '<p><strong>BSN:</strong> ' + escapeHtml(person.burgerservicenummer) + '</p>';
            }
            if (person.administratienummer) {
                html += '<p><strong>A-nummer:</strong> ' + escapeHtml(person.administratienummer) + '</p>';
            }
            html += '<div style="display: flex; gap: 8px; margin-top: 12px;">';
            html += '<button class="view-details-btn" data-bsn="' + escapeHtml(person.burgerservicenummer || '') + '">Bekijk details</button>';
            html += '<button class="prefill-btn" data-person=\'' + JSON.stringify(person).replace(/'/g, "&#39;") + '\'>Prefill formulier</button>';
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        
        if (pagination.total !== undefined) {
            html += '<div class="pagination-info">';
            html += 'Totaal: ' + pagination.total + ' resultaten';
            if (pagination.page && pagination.limit) {
                html += ' | Pagina ' + pagination.page + ' van ' + Math.ceil(pagination.total / pagination.limit);
            }
            html += '</div>';
        }
        
        resultsContent.innerHTML = html;
        
        // Event delegation for view details buttons and prefill buttons
        resultsContent.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('view-details-btn')) {
                var bsn = e.target.getAttribute('data-bsn');
                if (bsn) {
                    searchByBSN(bsn);
                }
            } else if (e.target && e.target.classList.contains('prefill-btn')) {
                var personData = e.target.getAttribute('data-person');
                if (personData) {
                    try {
                        var person = JSON.parse(personData.replace(/&#39;/g, "'"));
                        prefillForm(person);
                    } catch (err) {
                        console.error('Fout bij parsen persoon data:', err);
                        showError('Fout bij prefill: ' + err.message);
                    }
                }
            }
        });
    }

    function getApiType() {
        // Check welke tab actief is
        var activeTab = document.querySelector('.tab-button.active');
        if (activeTab) {
            return activeTab.getAttribute('data-tab') || 'bevragen';
        }
        
        // Fallback: check radio buttons (voor backward compatibility)
        var historieRadio = document.getElementById('api-historie');
        var bevragenRadio = document.getElementById('api-bevragen');
        
        if (historieRadio && historieRadio.checked) {
            return 'historie';
        }
        if (bevragenRadio && bevragenRadio.checked) {
            return 'bevragen';
        }
        
        return 'bevragen';
    }
    
    function getSchemaType() {
        var ggmRadio = document.getElementById('schema-ggm');
        var vrijbrpRadio = document.getElementById('schema-vrijbrp');
        
        if (ggmRadio && ggmRadio.checked) {
            return 'ggm';
        }
        if (vrijbrpRadio && vrijbrpRadio.checked) {
            return 'vrijbrp';
        }
        
        return 'vrijbrp';
    }
    
    function switchTab(tabName) {
        console.log('switchTab aangeroepen met:', tabName);
        
        // Update tab buttons
        var allTabs = document.querySelectorAll('.tab-button');
        console.log('Aantal tabs gevonden:', allTabs.length);
        allTabs.forEach(function(tab) {
            tab.classList.remove('active');
        });
        
        var activeTab = document.getElementById('tab-' + tabName);
        console.log('Actieve tab element:', activeTab);
        if (activeTab) {
            activeTab.classList.add('active');
            activeTab.style.display = 'flex';
            activeTab.style.visibility = 'visible';
            activeTab.style.opacity = '1';
        }
        
        // Update tab content
        var allContent = document.querySelectorAll('.tab-content');
        console.log('Aantal tab content elementen:', allContent.length);
        allContent.forEach(function(content) {
            content.classList.remove('active');
            content.style.display = 'none';
        });
        
        var activeContent = document.getElementById('tab-content-' + tabName);
        console.log('Actieve content element:', activeContent);
        if (activeContent) {
            activeContent.classList.add('active');
            activeContent.style.display = 'flex';
        }
        
        console.log('Tab switch voltooid voor:', tabName);
    }
    
    function updateApiUI() {
        var apiType = getApiType();
        switchTab(apiType);
    }

    function searchVerblijfplaatshistorie(bsn) {
        console.log('=== VERBLIJFPLAATSHISTORIE ZOEKEN START ===');
        console.log('BSN:', bsn);
        console.log('API_BASE:', API_BASE);
        
        if (!bsn || bsn.length !== 9) {
            showError('Ongeldig BSN. Voer precies 9 cijfers in.');
            return;
        }
        
        showLoading();
        hideError();
        
        var url = API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn) + '/verblijfplaatshistorie';
        console.log('Fetch URL:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'OCS-APIRequest': 'true'
            },
            credentials: 'include'
        })
        .then(function(response) {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                return response.text().then(function(text) {
                    console.error('Error response text:', text);
                    var errorData;
                    try {
                        errorData = JSON.parse(text);
                    } catch (e) {
                        errorData = { detail: text || 'HTTP ' + response.status };
                    }
                    throw new Error(errorData.detail || 'HTTP ' + response.status + ': ' + (errorData.title || 'Error'));
                });
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Historie data ontvangen:', data);
            displayVerblijfplaatshistorie(data, bsn);
            console.log('=== VERBLIJFPLAATSHISTORIE VOLTOOID ===');
        })
        .catch(function(error) {
            console.error('=== FOUT BIJ HISTORIE ZOEKEN ===');
            console.error('Error:', error);
            console.error('Error message:', error.message);
            showError('Fout bij ophalen verblijfplaatshistorie: ' + error.message);
            hideResults();
        })
        .finally(function() {
            hideLoading();
        });
    }
    
    function displayVerblijfplaatshistorie(data, bsn) {
        var resultsContainer = document.getElementById('results-container');
        var resultsContent = document.getElementById('results-content');
        
        if (!resultsContainer || !resultsContent) return;
        
        resultsContainer.style.display = 'block';
        hideError();
        
        var historie = data._embedded && data._embedded.verblijfplaatshistorie ? data._embedded.verblijfplaatshistorie : [];
        
        var html = '<div class="person-detail">';
        html += '<h3>Verblijfplaatshistorie voor BSN: ' + escapeHtml(bsn) + '</h3>';
        
        if (historie.length === 0) {
            html += '<div style="padding: 20px; color: var(--text-muted);">Geen verblijfplaatshistorie gevonden voor dit BSN.</div>';
        } else {
            html += '<div class="historie-list">';
            historie.forEach(function(verblijfplaats, index) {
                html += '<div class="historie-item" style="border: 1px solid var(--border-color); border-radius: 8px; padding: 16px; margin-bottom: 16px; background: var(--bg-card);">';
                html += '<h4 style="margin-top: 0; color: var(--text-primary);">Verblijfplaats ' + (index + 1) + '</h4>';
                
                if (verblijfplaats.straatnaam) {
                    html += '<div class="detail-row"><div class="detail-label">Straatnaam</div><div class="detail-value">' + escapeHtml(verblijfplaats.straatnaam) + '</div></div>';
                }
                if (verblijfplaats.huisnummer !== undefined) {
                    html += '<div class="detail-row"><div class="detail-label">Huisnummer</div><div class="detail-value">' + escapeHtml(verblijfplaats.huisnummer) + '</div></div>';
                }
                if (verblijfplaats.huisnummertoevoeging) {
                    html += '<div class="detail-row"><div class="detail-label">Huisnummertoevoeging</div><div class="detail-value">' + escapeHtml(verblijfplaats.huisnummertoevoeging) + '</div></div>';
                }
                if (verblijfplaats.postcode) {
                    html += '<div class="detail-row"><div class="detail-label">Postcode</div><div class="detail-value">' + escapeHtml(verblijfplaats.postcode) + '</div></div>';
                }
                if (verblijfplaats.woonplaatsnaam) {
                    html += '<div class="detail-row"><div class="detail-label">Woonplaats</div><div class="detail-value">' + escapeHtml(verblijfplaats.woonplaatsnaam) + '</div></div>';
                }
                if (verblijfplaats.datumAanvangAdres && verblijfplaats.datumAanvangAdres.datum) {
                    html += '<div class="detail-row"><div class="detail-label">Datum aanvang adres</div><div class="detail-value">' + escapeHtml(formatDate(verblijfplaats.datumAanvangAdres.datum)) + '</div></div>';
                }
                if (verblijfplaats.datumIngangGeldigheid && verblijfplaats.datumIngangGeldigheid.datum) {
                    html += '<div class="detail-row"><div class="detail-label">Datum ingang geldigheid</div><div class="detail-value">' + escapeHtml(formatDate(verblijfplaats.datumIngangGeldigheid.datum)) + '</div></div>';
                }
                if (verblijfplaats.datumEindeGeldigheid && verblijfplaats.datumEindeGeldigheid.datum) {
                    html += '<div class="detail-row"><div class="detail-label">Datum einde geldigheid</div><div class="detail-value">' + escapeHtml(formatDate(verblijfplaats.datumEindeGeldigheid.datum)) + '</div></div>';
                }
                
                html += '</div>';
            });
            html += '</div>';
        }
        
        html += '</div>';
        resultsContent.innerHTML = html;
    }
    
    function searchByBSN(bsn) {
        console.log('=== ZOEKEN OP BSN START ===');
        console.log('BSN:', bsn);
        console.log('API_BASE:', API_BASE);
        
        if (!bsn || bsn.length !== 9) {
            showError('Ongeldig BSN. Voer precies 9 cijfers in.');
            return;
        }
        
        showLoading();
        hideError();
        
        var schemaType = getSchemaType();
        // Voor vrijBRP: ggm=false (expliciet)
        // Voor GGM: ggm=true
        var ggmParam = schemaType === 'ggm' ? '&ggm=true' : '&ggm=false';
        var url = API_BASE + '/ingeschrevenpersonen?bsn=' + encodeURIComponent(bsn) + '&_limit=1' + ggmParam;
        console.log('=== SCHEMA TYPE ===');
        console.log('Schema type:', schemaType);
        console.log('GGM param:', ggmParam);
        console.log('Fetch URL:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'OCS-APIRequest': 'true'
            },
            credentials: 'include'
        })
        .then(function(response) {
            console.log('Response status:', response.status);
            console.log('Response ok:', response.ok);
            
            if (!response.ok) {
                return response.text().then(function(text) {
                    console.error('Error response text:', text);
                    var errorData;
                    try {
                        errorData = JSON.parse(text);
                    } catch (e) {
                        errorData = { detail: text || 'HTTP ' + response.status };
                    }
                    throw new Error(errorData.detail || 'HTTP ' + response.status + ': ' + (errorData.title || 'Error'));
                });
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Data ontvangen:', data);
            var persons = data._embedded && data._embedded.ingeschrevenpersonen ? data._embedded.ingeschrevenpersonen : [];
            
            if (persons.length === 0) {
                showError('Geen persoon gevonden met BSN: ' + bsn);
                hideResults();
            } else {
                displayPerson(persons[0]);
            }
            console.log('=== ZOEKEN VOLTOOID ===');
        })
        .catch(function(error) {
            console.error('=== FOUT BIJ ZOEKEN ===');
            console.error('Error:', error);
            console.error('Error message:', error.message);
            showError('Fout bij zoeken: ' + error.message);
            hideResults();
        })
        .finally(function() {
            hideLoading();
        });
    }

    function listPersons(limit) {
        showLoading();
        hideError();
        
        var schemaType = getSchemaType();
        // Voor vrijBRP: ggm=false (expliciet)
        // Voor GGM: ggm=true
        var ggmParam = schemaType === 'ggm' ? '&ggm=true' : '&ggm=false';
        var url = API_BASE + '/ingeschrevenpersonen?_limit=' + limit + '&_page=1' + ggmParam;
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'OCS-APIRequest': 'true'
            },
            credentials: 'include'
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    var errorData;
                    try {
                        errorData = JSON.parse(text);
                    } catch (e) {
                        errorData = { detail: text || 'HTTP ' + response.status };
                    }
                    throw new Error(errorData.detail || 'HTTP ' + response.status);
                });
            }
            return response.json();
        })
        .then(function(data) {
            displayPersonList(data);
        })
        .catch(function(error) {
            showError('Fout bij ophalen personen: ' + error.message);
            hideResults();
        })
        .finally(function() {
            hideLoading();
        });
    }

    function freeSearch(searchTerm) {
        console.log('=== VRIJ ZOEKEN START ===');
        console.log('Zoekterm:', searchTerm);
        console.log('API_BASE:', API_BASE);
        
        showLoading();
        hideError();
        
        var searchParams = {};
        
        if (/^\d{9}$/.test(searchTerm)) {
            searchParams.bsn = searchTerm;
            console.log('Zoektype: BSN');
        }
        else if (/^\d{4}[-]?\d{2}[-]?\d{2}$/.test(searchTerm)) {
            var dateStr = searchTerm.replace(/-/g, '');
            if (dateStr.length === 8) {
                searchParams.geboortedatum = dateStr.substring(0, 4) + '-' + dateStr.substring(4, 6) + '-' + dateStr.substring(6, 8);
            } else {
                searchParams.geboortedatum = searchTerm;
            }
            console.log('Zoektype: Geboortedatum');
        }
        else if (/^[\d.]+$/.test(searchTerm) && searchTerm.length >= 8) {
            searchParams.anummer = searchTerm;
            console.log('Zoektype: A-nummer');
        }
        else {
            searchParams.achternaam = searchTerm;
            console.log('Zoektype: Achternaam');
        }
        
        var schemaType = getSchemaType();
        // Voor vrijBRP: ggm=false (expliciet)
        // Voor GGM: ggm=true
        var ggmParam = schemaType === 'ggm' ? '&ggm=true' : '&ggm=false';
        
        var url = API_BASE + '/ingeschrevenpersonen?_limit=50' + ggmParam;
        for (var key in searchParams) {
            url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(searchParams[key]);
        }
        
        console.log('Fetch URL:', url);
        
        fetch(url, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'OCS-APIRequest': 'true'
            },
            credentials: 'include'
        })
        .then(function(response) {
            if (!response.ok) {
                return response.text().then(function(text) {
                    var errorData;
                    try {
                        errorData = JSON.parse(text);
                    } catch (e) {
                        errorData = { detail: text || 'HTTP ' + response.status };
                    }
                    throw new Error(errorData.detail || 'HTTP ' + response.status);
                });
            }
            return response.json();
        })
        .then(function(data) {
            var persons = data._embedded && data._embedded.ingeschrevenpersonen ? data._embedded.ingeschrevenpersonen : [];
            
            if (persons.length === 0) {
                showError('Geen resultaten gevonden voor: ' + searchTerm);
                hideResults();
            } else if (persons.length === 1) {
                displayPerson(persons[0]);
            } else {
                displayPersonList(data);
            }
        })
        .catch(function(error) {
            showError('Fout bij zoeken: ' + error.message);
            hideResults();
        })
        .finally(function() {
            hideLoading();
        });
    }

    function init() {
        if (initialized) {
            return;
        }
        
        API_BASE = window.location.origin + '/apps/openregister';
        console.log('=== INITIALISATIE ===');
        console.log('API_BASE:', API_BASE);
        
        var bsnInput = document.getElementById('bsn-input');
        var searchBsnBtn = document.getElementById('search-bsn-btn');
        var limitInput = document.getElementById('limit-input');
        var listPersonsBtn = document.getElementById('list-persons-btn');
        var freeSearchInput = document.getElementById('free-search-input');
        var freeSearchBtn = document.getElementById('free-search-btn');
        var closeFormBtn = document.getElementById('close-form-btn');
        var clearFormBtn = document.getElementById('clear-form-btn');
        var testForm = document.getElementById('test-form');
        
        if (!bsnInput || !searchBsnBtn || !limitInput || !listPersonsBtn || !freeSearchInput || !freeSearchBtn) {
            console.warn('Niet alle elementen gevonden, probeer later opnieuw...');
            return;
        }
        
        initialized = true;
        
        console.log('=== TAB INITIALISATIE START ===');
        
        // Tab button event listeners
        var tabBevragen = document.getElementById('tab-bevragen');
        var tabHistorie = document.getElementById('tab-historie');
        
        console.log('Tab elementen opgehaald');
        var tabsContainer = document.querySelector('.tabs-container');
        var tabsNav = document.querySelector('.tabs-nav');
        var allTabButtons = document.querySelectorAll('.tab-button');
        
        console.log('Tabs container gevonden:', !!tabsContainer);
        console.log('Tabs nav gevonden:', !!tabsNav);
        console.log('Aantal tab buttons gevonden:', allTabButtons.length);
        console.log('Tab bevragen gevonden:', !!tabBevragen);
        console.log('Tab historie gevonden:', !!tabHistorie);
        
        if (tabsContainer) {
            console.log('Tabs container styles:', window.getComputedStyle(tabsContainer).display);
            console.log('Tabs container visibility:', window.getComputedStyle(tabsContainer).visibility);
        }
        
        if (tabBevragen) {
            console.log('Bevragen tab element:', tabBevragen);
            console.log('Bevragen tab styles:', window.getComputedStyle(tabBevragen).display);
            tabBevragen.addEventListener('click', function() {
                console.log('Bevragen tab geklikt!');
                switchTab('bevragen');
            });
        } else {
            console.error('Tab bevragen NIET gevonden!');
            console.error('Zoek naar element met id="tab-bevragen"');
            console.error('Gevonden elementen:', document.querySelectorAll('[id*="tab"]'));
        }
        
        if (tabHistorie) {
            console.log('Historie tab element:', tabHistorie);
            console.log('Historie tab styles:', window.getComputedStyle(tabHistorie).display);
            tabHistorie.addEventListener('click', function() {
                console.log('Historie tab geklikt!');
                switchTab('historie');
            });
        } else {
            console.error('Tab historie NIET gevonden!');
            console.error('Zoek naar element met id="tab-historie"');
        }
        
        // Historie zoek button
        var searchHistorieBtn = document.getElementById('search-historie-btn');
        var historieBsnInput = document.getElementById('historie-bsn-input');
        if (searchHistorieBtn) {
            searchHistorieBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var bsn = historieBsnInput ? historieBsnInput.value.trim() : '';
                if (!bsn) {
                    showError('Voer een BSN in');
                    return;
                }
                if (!/^\d{9}$/.test(bsn)) {
                    showError('BSN moet precies 9 cijfers zijn');
                    return;
                }
                searchVerblijfplaatshistorie(bsn);
            });
        }
        if (historieBsnInput) {
            historieBsnInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchHistorieBtn) searchHistorieBtn.click();
                }
            });
        }
        
        // Initialiseer UI - start met bevragen tab
        switchTab('bevragen');
        
        // Formulier event listeners
        if (closeFormBtn) {
            closeFormBtn.addEventListener('click', function() {
                var formContainer = document.getElementById('test-form-container');
                if (formContainer) {
                    formContainer.style.display = 'none';
                }
            });
        }
        
        if (clearFormBtn) {
            clearFormBtn.addEventListener('click', function() {
                clearForm();
            });
        }
        
        if (testForm) {
            testForm.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(testForm);
                var data = {};
                for (var pair of formData.entries()) {
                    data[pair[0]] = pair[1];
                }
                console.log('Formulier ingediend:', data);
                alert('Formulier ingediend! (Dit is een test - data wordt niet opgeslagen)\n\nData: ' + JSON.stringify(data, null, 2));
            });
        }
        
        if (searchBsnBtn) {
            searchBsnBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var currentBsnInput = document.getElementById('bsn-input');
                if (!currentBsnInput) {
                    showError('BSN input veld niet gevonden');
                    return;
                }
                var bsn = currentBsnInput.value.trim();
                if (!bsn) {
                    showError('Voer een BSN in');
                    return;
                }
                if (!/^\d{9}$/.test(bsn)) {
                    showError('BSN moet precies 9 cijfers zijn');
                    return;
                }
                searchByBSN(bsn);
            });
        }
        
        if (bsnInput) {
            bsnInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    if (searchBsnBtn) searchBsnBtn.click();
                }
            });
        }
        
        if (listPersonsBtn) {
            listPersonsBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var limit = limitInput ? parseInt(limitInput.value) || 10 : 10;
                listPersons(limit);
            });
        }
        
        if (freeSearchBtn && freeSearchInput) {
            freeSearchBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var searchTerm = freeSearchInput.value.trim();
                if (!searchTerm) {
                    showError('Voer een zoekterm in');
                    return;
                }
                freeSearch(searchTerm);
            });
            
            freeSearchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    freeSearchBtn.click();
                }
            });
        }
        
        var schemaRadios = document.querySelectorAll('input[name="schema-type"]');
        var schemaHint = document.getElementById('schema-hint');
        
        if (schemaRadios.length > 0 && schemaHint) {
            schemaRadios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    if (this.value === 'vrijbrp') {
                        schemaHint.textContent = 'Zoekt via Haal Centraal API in schema\'s die niet GGM zijn (schema ID 6 - Personen)';
                    } else {
                        schemaHint.textContent = 'Zoekt via Haal Centraal API in GGM schema (schema ID 21 - GGM IngeschrevenPersoon)';
                    }
                });
            });
        }
        
        console.log('=== TAB INITIALISATIE VOLTOOID ===');
        console.log('=== INITIALISATIE VOLTOOID ===');
    }

    /**
     * Prefill het test formulier met persoon data
     */
    function prefillForm(person) {
        var formContainer = document.getElementById('test-form-container');
        if (!formContainer) {
            showError('Formulier container niet gevonden');
            return;
        }
        
        // Toon formulier
        formContainer.style.display = 'block';
        // Scroll naar formulier met kleine delay zodat display eerst wordt toegepast
        setTimeout(function() {
            formContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
        
        var naam = person.naam || {};
        var geboorte = person.geboorte || {};
        var verblijfplaats = person.verblijfplaats || {};
        
        // Persoonlijke gegevens
        var bsnInput = document.getElementById('form-bsn');
        if (bsnInput) bsnInput.value = person.burgerservicenummer || '';
        
        var voornamenInput = document.getElementById('form-voornamen');
        if (voornamenInput) {
            var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
            voornamenInput.value = voornamen;
        }
        
        var voorvoegselInput = document.getElementById('form-voorvoegsel');
        if (voorvoegselInput) voorvoegselInput.value = naam.voorvoegsel || '';
        
        var geslachtsnaamInput = document.getElementById('form-geslachtsnaam');
        if (geslachtsnaamInput) geslachtsnaamInput.value = naam.geslachtsnaam || '';
        
        var geboortedatumInput = document.getElementById('form-geboortedatum');
        if (geboortedatumInput && geboorte.datum && geboorte.datum.datum) {
            // Format datum naar YYYY-MM-DD voor date input
            var datum = geboorte.datum.datum;
            if (datum.match(/^\d{4}-\d{2}-\d{2}$/)) {
                geboortedatumInput.value = datum;
            } else if (datum.match(/^\d{8}$/)) {
                // Format van YYYYMMDD naar YYYY-MM-DD
                geboortedatumInput.value = datum.substring(0, 4) + '-' + datum.substring(4, 6) + '-' + datum.substring(6, 8);
            }
        }
        
        var geboorteplaatsInput = document.getElementById('form-geboorteplaats');
        if (geboorteplaatsInput) geboorteplaatsInput.value = geboorte.plaats || '';
        
        var geslachtInput = document.getElementById('form-geslacht');
        if (geslachtInput) {
            var geslacht = person.geslachtsaanduiding || person.geslacht || '';
            geslachtInput.value = geslacht;
        }
        
        var anummerInput = document.getElementById('form-anummer');
        if (anummerInput) anummerInput.value = person.aNummer || person.administratienummer || '';
        
        // Adresgegevens
        var straatnaamInput = document.getElementById('form-straatnaam');
        if (straatnaamInput) straatnaamInput.value = verblijfplaats.straatnaam || '';
        
        var huisnummerInput = document.getElementById('form-huisnummer');
        if (huisnummerInput) huisnummerInput.value = verblijfplaats.huisnummer || '';
        
        var huisnummertoevoegingInput = document.getElementById('form-huisnummertoevoeging');
        if (huisnummertoevoegingInput) huisnummertoevoegingInput.value = verblijfplaats.huisnummertoevoeging || '';
        
        var postcodeInput = document.getElementById('form-postcode');
        if (postcodeInput) postcodeInput.value = verblijfplaats.postcode || '';
        
        var woonplaatsInput = document.getElementById('form-woonplaats');
        if (woonplaatsInput) woonplaatsInput.value = verblijfplaats.woonplaatsnaam || verblijfplaats.woonplaats || '';
        
        console.log('Formulier geprefill met persoon data:', person);
    }
    
    /**
     * Wis het formulier
     */
    function clearForm() {
        var form = document.getElementById('test-form');
        if (form) {
            form.reset();
        }
    }
    
    function tryInit() {
        var testBtn = document.getElementById('search-bsn-btn');
        if (testBtn) {
            init();
        } else {
            setTimeout(tryInit, 100);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', tryInit);
    } else {
        tryInit();
    }

    setTimeout(function() {
        if (!initialized) {
            var testBtn = document.getElementById('search-bsn-btn');
            if (testBtn) {
                initialized = false;
                init();
            }
        }
    }, 1000);
})();
</script>

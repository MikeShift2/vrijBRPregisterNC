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
    <div id="prefill-results" class="results-overlay" style="display: none; z-index: 1000;">
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
                <div class="category-item" data-category="inschrijving">
                    <span class="category-number">01.</span>
                    <span class="category-name">Inschrijving</span>
                </div>
                <div class="category-item active" data-category="persoon">
                    <span class="category-number">02.</span>
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
                    <span class="category-number">03.</span>
                    <span class="category-name">Ouder 1</span>
                </div>
                <div class="category-item" data-category="ouder2">
                    <span class="category-number">04.</span>
                    <span class="category-name">Ouder 2</span>
                </div>
                <div class="category-item" data-category="nationaliteit">
                    <span class="category-number">05.</span>
                    <span class="category-name">Nationaliteit</span>
                    <span class="badge-count" id="nationaliteit-badge" style="display: none;">0</span>
                </div>
                <div class="category-item" data-category="partner">
                    <span class="category-number">06.</span>
                    <span class="category-name">Huwelijk/Geregistreerd partnerschap</span>
                </div>
                <div class="category-item" data-category="verblijfplaats">
                    <span class="category-number">07.</span>
                    <span class="category-name">Verblijfplaats (adres)</span>
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
                            <span class="badge-count" id="verblijfplaats-historie-badge" style="display: none;">0</span>
                        </div>
                    </div>
                </div>
                <div class="category-item" data-category="verblijfstitel">
                    <span class="category-number">08.</span>
                    <span class="category-name">Verblijfstitel</span>
                </div>
                <div class="category-item" data-category="verblijf-buitenland">
                    <span class="category-number">09.</span>
                    <span class="category-name">Verblijf in het buitenland</span>
                </div>
                <div class="category-item" data-category="kinderen">
                    <span class="category-number">10.</span>
                    <span class="category-name">Kind</span>
                    <span class="badge-count" id="kinderen-badge" style="display: none;">0</span>
                </div>
                <div class="category-item" data-category="overlijden">
                    <span class="category-number">11.</span>
                    <span class="category-name">Overlijden</span>
                </div>
                <div class="category-item" data-category="verblijfsaantekening">
                    <span class="category-number">12.</span>
                    <span class="category-name">Verblijfsaantekening EU/EER</span>
                </div>
                <div class="category-item" data-category="gezag">
                    <span class="category-number">13.</span>
                    <span class="category-name">Gezag</span>
                </div>
                <div class="category-item" data-category="reisdocument">
                    <span class="category-number">14.</span>
                    <span class="category-name">Reisdocument</span>
                </div>
                <div class="category-item" data-category="kiesrecht">
                    <span class="category-number">15.</span>
                    <span class="category-name">Kiesrecht</span>
                </div>
                <div class="category-item" data-category="verwijzing">
                    <span class="category-number">16.</span>
                    <span class="category-name">Verwijzing</span>
                </div>
                <div class="category-item" data-category="contactgegevens">
                    <span class="category-number">21.</span>
                    <span class="category-name">Contactgegevens (optioneel)</span>
                </div>
                </div>
                
            <!-- Details Panel -->
            <div class="details-panel">
                <div class="details-header">
                    <h2 class="details-title" id="details-title">Details: 02. Persoon</h2>
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
                            <div class="detail-label">01.10 a-nummer</div>
                            <div class="detail-value" id="detail-anummer">-</div>
                            </div>
                        <div class="detail-row">
                            <div class="detail-label">01.20 burgerservicenummer</div>
                            <div class="detail-value" id="detail-bsn">-</div>
                            </div>
                        <div class="detail-row">
                            <div class="detail-label">02.10 voornamen</div>
                            <div class="detail-value" id="detail-voornamen">-</div>
                            </div>
                        <div class="detail-row">
                            <div class="detail-label">02.40 geslachtsnaam</div>
                            <div class="detail-value" id="detail-geslachtsnaam">-</div>
                            </div>
                        <div class="detail-row">
                            <div class="detail-label">03.10 geboortedatum</div>
                            <div class="detail-value" id="detail-geboortedatum">-</div>
                            </div>
                        <div class="detail-row">
                            <div class="detail-label">03.20 geboorteplaats</div>
                            <div class="detail-value" id="detail-geboorteplaats">-</div>
                            </div>
                        <div class="detail-row">
                            <div class="detail-label">03.30 geboorteland</div>
                            <div class="detail-value" id="detail-geboorteland">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">04.10 geslachtsaanduiding</div>
                            <div class="detail-value" id="detail-geslachtsaanduiding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">62.10 datum ingang familierechtelijke betrekking</div>
                            <div class="detail-value" id="detail-persoon-datum-ingang-familierechtelijke-betrekking">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.10 gemeente document</div>
                            <div class="detail-value" id="detail-persoon-gemeente-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.20 datum document</div>
                            <div class="detail-value" id="detail-persoon-datum-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.30 beschrijving document</div>
                            <div class="detail-value" id="detail-persoon-beschrijving-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-persoon-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-persoon-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-persoon-datum-opneming">-</div>
                        </div>
                        </div>
                    </div>
                    
                <!-- Ouder 1 Details -->
                <div class="detail-section" data-category="ouder1" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">01.20 burgerservicenummer</div>
                            <div class="detail-value" id="detail-ouder1-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.10 voornamen</div>
                            <div class="detail-value" id="detail-ouder1-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.40 geslachtsnaam</div>
                            <div class="detail-value" id="detail-ouder1-geslachtsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">62.10 datum ingang familierechtelijke betrekking</div>
                            <div class="detail-value" id="detail-ouder1-datum-ingang-familierechtelijke-betrekking">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.10 gemeente document</div>
                            <div class="detail-value" id="detail-ouder1-gemeente-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.20 datum document</div>
                            <div class="detail-value" id="detail-ouder1-datum-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.30 beschrijving document</div>
                            <div class="detail-value" id="detail-ouder1-beschrijving-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-ouder1-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-ouder1-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-ouder1-datum-opneming">-</div>
                        </div>
                        </div>
                    </div>
                    
                <!-- Ouder 2 Details -->
                <div class="detail-section" data-category="ouder2" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">01.20 burgerservicenummer</div>
                            <div class="detail-value" id="detail-ouder2-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.10 voornamen</div>
                            <div class="detail-value" id="detail-ouder2-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.40 geslachtsnaam</div>
                            <div class="detail-value" id="detail-ouder2-geslachtsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">05.10 nationaliteit</div>
                            <div class="detail-value" id="detail-ouder2-nationaliteit">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">63.10 reden opname nationaliteit</div>
                            <div class="detail-value" id="detail-ouder2-reden-opname-nationaliteit">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.10 gemeente document</div>
                            <div class="detail-value" id="detail-ouder2-gemeente-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.20 datum document</div>
                            <div class="detail-value" id="detail-ouder2-datum-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.30 beschrijving document</div>
                            <div class="detail-value" id="detail-ouder2-beschrijving-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-ouder2-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-ouder2-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-ouder2-datum-opneming">-</div>
                        </div>
    </div>
</div>

                <!-- Partner Details -->
                <div class="detail-section" data-category="partner" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">01.10 a-nummer</div>
                            <div class="detail-value" id="detail-partner-anummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">01.20 burgerservicenummer</div>
                            <div class="detail-value" id="detail-partner-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.10 voornamen</div>
                            <div class="detail-value" id="detail-partner-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.40 geslachtsnaam</div>
                            <div class="detail-value" id="detail-partner-geslachtsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.10 geboortedatum</div>
                            <div class="detail-value" id="detail-partner-geboortedatum">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.20 geboorteplaats</div>
                            <div class="detail-value" id="detail-partner-geboorteplaats">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.30 geboorteland</div>
                            <div class="detail-value" id="detail-partner-geboorteland">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">04.10 geslachtsaanduiding</div>
                            <div class="detail-value" id="detail-partner-geslachtsaanduiding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">06.10 datum huwelijkssluiting/aangaan GPS</div>
                            <div class="detail-value" id="detail-partner-datum-huwelijkssluiting">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">06.20 plaats huwelijkssluiting/aangaan GPS</div>
                            <div class="detail-value" id="detail-partner-plaats-huwelijkssluiting">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">06.30 land huwelijkssluiting/aangaan GPS</div>
                            <div class="detail-value" id="detail-partner-land-huwelijkssluiting">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">15.10 soort verbintenis</div>
                            <div class="detail-value" id="detail-partner-soort-verbintenis">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.10 gemeente document</div>
                            <div class="detail-value" id="detail-partner-gemeente-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.20 datum document</div>
                            <div class="detail-value" id="detail-partner-datum-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.30 beschrijving document</div>
                            <div class="detail-value" id="detail-partner-beschrijving-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-partner-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-partner-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-partner-datum-opneming">-</div>
                        </div>
                    </div>
                </div>

                <!-- Verblijfplaats Details -->
                <div class="detail-section" data-category="verblijfplaats" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid" id="verblijfplaats-actueel">
                        <div class="detail-row">
                            <div class="detail-label">09.10 gemeente van inschrijving</div>
                            <div class="detail-value" id="detail-verblijfplaats-gemeente-inschrijving">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">09.11 code gemeente van inschrijving</div>
                            <div class="detail-value" id="detail-verblijfplaats-code-gemeente-inschrijving">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">09.20 datum inschrijving</div>
                            <div class="detail-value" id="detail-verblijfplaats-datum-inschrijving">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">10.10 functie adres</div>
                            <div class="detail-value" id="detail-verblijfplaats-functie-adres">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">10.20 gemeentedeel</div>
                            <div class="detail-value" id="detail-verblijfplaats-gemeentedeel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">10.30 datum aanvang adreshouding</div>
                            <div class="detail-value" id="detail-verblijfplaats-datum-aanvang-adreshouding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.10 straatnaam</div>
                            <div class="detail-value" id="detail-verblijfplaats-straatnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.11 straatnaam (officieel)</div>
                            <div class="detail-value" id="detail-verblijfplaats-straatnaam-officieel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.12 straatnaam (NEN)</div>
                            <div class="detail-value" id="detail-verblijfplaats-straatnaam-nen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.15 openbare ruimte</div>
                            <div class="detail-value" id="detail-verblijfplaats-openbare-ruimte">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.20 huisnummer</div>
                            <div class="detail-value" id="detail-verblijfplaats-huisnummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.60 postcode</div>
                            <div class="detail-value" id="detail-verblijfplaats-postcode">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.70 woonplaatsnaam</div>
                            <div class="detail-value" id="detail-verblijfplaats-woonplaatsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.80 identificatie verblijfplaats</div>
                            <div class="detail-value" id="detail-verblijfplaats-identificatie-verblijfplaats">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.90 identificatiecode nummeraanduiding</div>
                            <div class="detail-value" id="detail-verblijfplaats-identificatiecode-nummeraanduiding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">14.10 land vanwaar ingeschreven</div>
                            <div class="detail-value" id="detail-verblijfplaats-land-vanwaar-ingeschreven">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">14.20 datum vestiging in Nederland</div>
                            <div class="detail-value" id="detail-verblijfplaats-datum-vestiging-nederland">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">72.10 omschrijving van de aangifte adreshouding</div>
                            <div class="detail-value" id="detail-verblijfplaats-omschrijving-aangifte-adreshouding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-verblijfplaats-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-verblijfplaats-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-verblijfplaats-datum-opneming">-</div>
                        </div>
                    </div>
                    
                    <!-- Verblijfplaats Historie -->
                    <div class="detail-section-title" id="verblijfplaats-historie-title" style="display: none; margin-top: 32px;">Historie</div>
                    <div id="verblijfplaats-historie-list" class="verblijfplaats-historie-list" style="display: none;">
                        <!-- Historische adressen worden hier dynamisch toegevoegd -->
                    </div>
                </div>

                <!-- Kinderen Details -->
                <div class="detail-section" data-category="kinderen" style="display: none;">
                    <div class="detail-section-title">Kind</div>
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

                <!-- Inschrijving Details -->
                <div class="detail-section" data-category="inschrijving" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">01.10 a-nummer</div>
                            <div class="detail-value" id="detail-inschrijving-anummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">01.20 burgerservicenummer</div>
                            <div class="detail-value" id="detail-inschrijving-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.10 gemeente document</div>
                            <div class="detail-value" id="detail-inschrijving-gemeente-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.20 datum document</div>
                            <div class="detail-value" id="detail-inschrijving-datum-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.30 beschrijving document</div>
                            <div class="detail-value" id="detail-inschrijving-beschrijving-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-inschrijving-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-inschrijving-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-inschrijving-datum-opneming">-</div>
                        </div>
                    </div>
                </div>

                <!-- Verblijfstitel Details -->
                <div class="detail-section" data-category="verblijfstitel" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">09.10 gemeente van inschrijving</div>
                            <div class="detail-value" id="detail-verblijfstitel-gemeente-inschrijving">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">09.11 code gemeente van inschrijving</div>
                            <div class="detail-value" id="detail-verblijfstitel-code-gemeente-inschrijving">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">09.20 datum inschrijving</div>
                            <div class="detail-value" id="detail-verblijfstitel-datum-inschrijving">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">10.10 functie adres</div>
                            <div class="detail-value" id="detail-verblijfstitel-functie-adres">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">10.20 gemeentedeel</div>
                            <div class="detail-value" id="detail-verblijfstitel-gemeentedeel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">10.30 datum aanvang adreshouding</div>
                            <div class="detail-value" id="detail-verblijfstitel-datum-aanvang-adreshouding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.10 straatnaam</div>
                            <div class="detail-value" id="detail-verblijfstitel-straatnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.11 straatnaam (officieel)</div>
                            <div class="detail-value" id="detail-verblijfstitel-straatnaam-officieel">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.12 straatnaam (NEN)</div>
                            <div class="detail-value" id="detail-verblijfstitel-straatnaam-nen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.15 openbare ruimte</div>
                            <div class="detail-value" id="detail-verblijfstitel-openbare-ruimte">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.20 huisnummer</div>
                            <div class="detail-value" id="detail-verblijfstitel-huisnummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.60 postcode</div>
                            <div class="detail-value" id="detail-verblijfstitel-postcode">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.70 woonplaatsnaam</div>
                            <div class="detail-value" id="detail-verblijfstitel-woonplaatsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.80 identificatie verblijfplaats</div>
                            <div class="detail-value" id="detail-verblijfstitel-identificatie-verblijfplaats">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.90 identificatiecode nummeraanduiding</div>
                            <div class="detail-value" id="detail-verblijfstitel-identificatiecode-nummeraanduiding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">14.10 land vanwaar ingeschreven</div>
                            <div class="detail-value" id="detail-verblijfstitel-land-vanwaar-ingeschreven">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">14.20 datum vestiging in Nederland</div>
                            <div class="detail-value" id="detail-verblijfstitel-datum-vestiging-nederland">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">72.10 omschrijving van de aangifte adreshouding</div>
                            <div class="detail-value" id="detail-verblijfstitel-omschrijving-aangifte-adreshouding">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-verblijfstitel-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-verblijfstitel-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-verblijfstitel-datum-opneming">-</div>
                        </div>
                    </div>
                </div>

                <!-- Verblijf in het buitenland Details -->
                <div class="detail-section" data-category="verblijf-buitenland" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">01.10 a-nummer</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-anummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">01.20 burgerservicenummer</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.10 voornamen</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-voornamen">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">02.40 geslachtsnaam</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-geslachtsnaam">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.10 geboortedatum</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-geboortedatum">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.20 geboorteplaats</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-geboorteplaats">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">03.30 geboorteland</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-geboorteland">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.10 gemeente document</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-gemeente-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.20 datum document</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-datum-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">82.30 beschrijving document</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-beschrijving-document">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.10 ingangsdatum geldigheid</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-ingangsdatum-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">85.11 volgcode geldigheid</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-volgcode-geldigheid">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">86.10 datum van opneming</div>
                            <div class="detail-value" id="detail-verblijf-buitenland-datum-opneming">-</div>
                        </div>
                    </div>
                </div>

                <!-- Overlijden Details -->
                <div class="detail-section" data-category="overlijden" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">11.10 datum overlijden</div>
                            <div class="detail-value" id="detail-overlijden-datum">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.20 plaats overlijden</div>
                            <div class="detail-value" id="detail-overlijden-plaats">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">11.30 land overlijden</div>
                            <div class="detail-value" id="detail-overlijden-land">-</div>
                        </div>
                    </div>
                </div>

                <!-- Verblijfsaantekening EU/EER Details -->
                <div class="detail-section" data-category="verblijfsaantekening" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">12.10 aantekening</div>
                            <div class="detail-value" id="detail-verblijfsaantekening">-</div>
                        </div>
                    </div>
                </div>

                <!-- Gezag Details -->
                <div class="detail-section" data-category="gezag" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">13.10 burgerservicenummer gezagdrager</div>
                            <div class="detail-value" id="detail-gezag-bsn">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">13.20 naam gezagdrager</div>
                            <div class="detail-value" id="detail-gezag-naam">-</div>
                        </div>
                    </div>
                </div>

                <!-- Reisdocument Details -->
                <div class="detail-section" data-category="reisdocument" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">14.10 documentnummer</div>
                            <div class="detail-value" id="detail-reisdocument-nummer">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">14.20 type</div>
                            <div class="detail-value" id="detail-reisdocument-type">-</div>
                        </div>
                    </div>
                </div>

                <!-- Kiesrecht Details -->
                <div class="detail-section" data-category="kiesrecht" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">15.10 kiesrecht</div>
                            <div class="detail-value" id="detail-kiesrecht">-</div>
                        </div>
                    </div>
                </div>

                <!-- Verwijzing Details -->
                <div class="detail-section" data-category="verwijzing" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">16.10 type verwijzing</div>
                            <div class="detail-value" id="detail-verwijzing-type">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">16.20 verwijzing naar</div>
                            <div class="detail-value" id="detail-verwijzing-naar">-</div>
                        </div>
                    </div>
                </div>

                <!-- Contactgegevens Details -->
                <div class="detail-section" data-category="contactgegevens" style="display: none;">
                    <div class="detail-section-title">Actueel</div>
                    <div class="detail-grid">
                        <div class="detail-row">
                            <div class="detail-label">21.10 telefoonnummer</div>
                            <div class="detail-value" id="detail-contact-telefoon">-</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">21.20 e-mailadres</div>
                            <div class="detail-value" id="detail-contact-email">-</div>
                        </div>
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
    height: 100vh;
    width: 100%;
    padding: 0;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
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
    width: 100%;
    flex: 1;
    position: relative;
    z-index: 1;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    box-sizing: border-box;
}

#person-display {
    display: none !important;
}

#person-display.visible,
#person-display[style*="block"] {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
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
    flex: 1;
    overflow: visible;
    min-height: 0;
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
    overflow-y: auto;
    overflow-x: hidden;
    max-height: calc(100vh - 200px);
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
    margin-left: 8px;
    margin-top: 4px;
    padding: 4px 0;
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 150px;
}

.category-item.active .category-submenu {
    display: flex;
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
    overflow-y: auto;
    overflow-x: hidden;
    min-height: 0;
    max-height: calc(100vh - 200px);
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
    display: flex;
    flex-direction: column;
    gap: 0;
}

.detail-row {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 0;
    border-bottom: 1px solid var(--border-color);
    min-height: 40px;
    align-items: center;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: var(--text-secondary);
    font-size: 13px;
    font-weight: 500;
    padding: 10px 16px 10px 0;
    display: flex;
    align-items: center;
}

.detail-value {
    color: var(--text-primary);
    font-size: 13px;
    padding: 10px 0;
    display: flex;
    align-items: center;
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
    display: flex;
    flex-direction: column;
    gap: 0;
}

.kind-detail-grid .detail-row,
.nationaliteit-detail-grid .detail-row {
    grid-template-columns: 200px 1fr;
}

/* Verblijfplaats Historie */
.verblijfplaats-historie-list {
    display: flex;
    flex-direction: column;
    gap: 24px;
    margin-top: 16px;
}

.historie-item {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 16px;
}

.historie-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-color);
}

.historie-item-header h5 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.historie-datum {
    font-size: 12px;
    color: var(--text-secondary);
    margin-left: 12px;
}

.no-historie {
    padding: 24px;
    text-align: center;
    color: var(--text-secondary);
    font-style: italic;
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

/* Forceer scrolling op body en html voor Nextcloud */
html, body {
    height: 100% !important;
    overflow: visible !important;
}

#content, #app-content {
    overflow-y: auto !important;
    overflow-x: hidden !important;
    height: 100% !important;
    max-height: none !important;
    width: 100% !important;
    background: var(--bg-primary) !important;
}

#app-content-wrapper {
    height: 100% !important;
    overflow: visible !important;
    width: 100% !important;
    background: var(--bg-primary) !important;
}

/* Verberg Nextcloud sidebar en andere elementen die ruimte innemen */
#app-sidebar,
#app-navigation,
.app-sidebar-header {
    display: none !important;
}

/* Zorg dat de main content area de volledige breedte gebruikt */
#app-content-vue,
#content-vue,
.app-content {
    width: 100% !important;
    margin-left: 0 !important;
    padding-left: 0 !important;
}
</style>

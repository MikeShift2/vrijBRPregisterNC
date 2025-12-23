<?php
/**
 * BRP Proces Test Pagina
 * 
 * Volledige simulatie van een BRP proces met alle componenten
 */

script('openregister', 'brp-proces-test');
style('openregister', 'brp-proces-test');
?>

<div id="brp-proces-test" class="brp-proces-test-container">
    <div class="brp-proces-header">
        <h1>BRP Proces Test Simulatie</h1>
        <p class="subtitle">Test een volledig burgerzaken proces met alle geïmplementeerde componenten</p>
    </div>

    <!-- Proces Stappen -->
    <div class="proces-stappen">
        <div class="stap active" data-stap="1">
            <div class="stap-nummer">1</div>
            <div class="stap-titel">Zaak Aanmaken</div>
        </div>
        <div class="stap" data-stap="2">
            <div class="stap-nummer">2</div>
            <div class="stap-titel">Persoon Opzoeken</div>
        </div>
        <div class="stap" data-stap="3">
            <div class="stap-nummer">3</div>
            <div class="stap-titel">Mutatie Indienen</div>
        </div>
        <div class="stap" data-stap="4">
            <div class="stap-nummer">4</div>
            <div class="stap-titel">Validatie</div>
        </div>
        <div class="stap" data-stap="5">
            <div class="stap-nummer">5</div>
            <div class="stap-titel">Documenten</div>
        </div>
        <div class="stap" data-stap="6">
            <div class="stap-nummer">6</div>
            <div class="stap-titel">Status</div>
        </div>
    </div>

    <!-- Stap 1: Zaak Aanmaken -->
    <div class="proces-stap-content" id="stap-1">
        <h2>Stap 1: Zaak Aanmaken</h2>
        <div class="form-group">
            <label>Zaaktype:</label>
            <select id="zaaktype" class="form-control">
                <option value="">Selecteer zaaktype</option>
                <option value="verhuizing">Verhuizing</option>
                <option value="geboorte">Geboorte</option>
                <option value="partnerschap">Partnerschap</option>
                <option value="overlijden">Overlijden</option>
            </select>
        </div>
        <div class="form-group">
            <label>Omschrijving:</label>
            <textarea id="zaak-omschrijving" class="form-control" rows="3" placeholder="Beschrijf de zaak..."></textarea>
        </div>
        <div class="form-group">
            <label>BSN (optioneel):</label>
            <input type="text" id="zaak-bsn" class="form-control" placeholder="123456789" maxlength="9">
        </div>
        <button type="button" class="btn btn-primary" id="btn-create-zaak">Zaak Aanmaken</button>
        <div id="zaak-result" class="result-box"></div>
    </div>

    <!-- Stap 2: Persoon Opzoeken -->
    <div class="proces-stap-content" id="stap-2" style="display: none;">
        <h2>Stap 2: Persoon Opzoeken</h2>
        <div class="form-group">
            <label>BSN:</label>
            <input type="text" id="search-bsn" class="form-control" placeholder="123456789" maxlength="9">
        </div>
        <div class="form-group">
            <label>Of zoek op achternaam:</label>
            <input type="text" id="search-achternaam" class="form-control" placeholder="Jansen">
        </div>
        <button type="button" class="btn btn-primary" id="btn-search-person">Zoeken</button>
        <div id="person-result" class="result-box"></div>
        <div id="person-details" class="person-details"></div>
    </div>

    <!-- Stap 3: Mutatie Indienen -->
    <div class="proces-stap-content" id="stap-3" style="display: none;">
        <h2>Stap 3: Mutatie Indienen</h2>
        <div class="form-group">
            <label>Mutatie Type:</label>
            <select id="mutatie-type" class="form-control">
                <option value="">Selecteer mutatie type</option>
                <option value="verhuizing">Verhuizing</option>
                <option value="geboorte">Geboorte</option>
                <option value="partnerschap">Partnerschap</option>
                <option value="overlijden">Overlijden</option>
            </select>
        </div>
        <div id="mutatie-form"></div>
        <button type="button" class="btn btn-primary" id="btn-submit-mutatie">Mutatie Indienen</button>
        <div id="mutatie-result" class="result-box"></div>
    </div>

    <!-- Stap 4: Validatie -->
    <div class="proces-stap-content" id="stap-4" style="display: none;">
        <h2>Stap 4: Validatie</h2>
        <div id="validatie-result" class="validatie-box"></div>
        <div id="validatie-details" class="validatie-details"></div>
        <button type="button" class="btn btn-primary" id="btn-next-to-documenten" style="margin-top: 20px; display: none;">Doorgaan naar Documenten</button>
    </div>

    <!-- Stap 5: Documenten -->
    <div class="proces-stap-content" id="stap-5" style="display: none;">
        <h2>Stap 5: Documenten</h2>
        <div class="form-group">
            <label>Document Upload:</label>
            <input type="file" id="document-upload" class="form-control" multiple>
        </div>
        <div class="form-group">
            <label>Document Type:</label>
            <select id="document-type" class="form-control">
                <option value="identiteitsbewijs">Identiteitsbewijs</option>
                <option value="verklaring">Verklaring</option>
                <option value="bewijs">Bewijs</option>
                <option value="overig">Overig</option>
            </select>
        </div>
        <button type="button" class="btn btn-primary" id="btn-upload-document">Document Uploaden</button>
        <div id="document-result" class="result-box"></div>
        <div id="document-list" class="document-list"></div>
        <button type="button" class="btn btn-success" id="btn-next-to-status" style="margin-top: 20px; display: none; font-weight: bold;">✓ Doorgaan naar Status Overzicht</button>
    </div>

    <!-- Stap 6: Status -->
    <div class="proces-stap-content" id="stap-6" style="display: none;">
        <h2>Stap 6: Status Overzicht</h2>
        <div id="status-overview" class="status-overview"></div>
        <button type="button" class="btn btn-primary" id="btn-refresh-status">Status Vernieuwen</button>
    </div>

    <!-- Proces Log -->
    <div class="proces-log">
        <h3>Proces Log</h3>
        <div id="proces-log-content" class="log-content"></div>
        <button type="button" class="btn btn-secondary" id="btn-clear-log">Log Wissen</button>
    </div>
</div>



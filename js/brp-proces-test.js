/**
 * BRP Proces Test JavaScript
 * CSP-compliant version
 */

(function() {
    'use strict';
    
    let currentStap = 1;
    let currentZaakId = null;
    let currentBsn = null;
    let currentMutatieId = null;
    let currentDocumenten = [];
    let initialized = false;

    // Initialize
    function init() {
        if (initialized) {
            return;
        }
        
        var logContent = document.getElementById('proces-log-content');
        if (!logContent) {
            console.warn('Log content niet gevonden, probeer later opnieuw...');
            return;
        }
        
        initialized = true;
        addLog('info', 'BRP Proces Test pagina geladen');
        updateStapDisplay();
        setupEventListeners();
    }

    // Helper to build Nextcloud-aware URLs (adds index.php when needed)
    function buildUrl(path) {
        let url = path;

        // Prefer Nextcloud helper when available
        if (typeof OC !== 'undefined' && typeof OC.generateUrl === 'function') {
            url = OC.generateUrl(path);
        }

        // Ensure index.php prefix is present for non-pretty-URL setups
        if (!url.includes('/index.php/')) {
            if (url.startsWith('/apps/')) {
                url = '/index.php' + url;
            } else if (!url.startsWith('/index.php')) {
                url = '/index.php' + (url.startsWith('/') ? '' : '/') + url;
            }
        }

        return url;
    }

    // Stap Management
    function updateStapDisplay() {
        document.querySelectorAll('.stap').forEach(function(stap, index) {
            const stapNum = index + 1;
            if (stapNum < currentStap) {
                stap.classList.add('completed');
                stap.classList.remove('active');
            } else if (stapNum === currentStap) {
                stap.classList.add('active');
                stap.classList.remove('completed');
            } else {
                stap.classList.remove('active', 'completed');
            }
        });

        document.querySelectorAll('.proces-stap-content').forEach(function(content, index) {
            if (index + 1 === currentStap) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    }

    function nextStap() {
        console.log('nextStap aangeroepen, huidige stap:', currentStap);
        if (currentStap < 6) {
            currentStap++;
            console.log('Nieuwe stap:', currentStap);
            updateStapDisplay();
            addLog('info', 'Stap ' + currentStap + ' gestart');
        } else {
            console.log('Al op laatste stap (6), geen navigatie');
            addLog('info', 'Al op laatste stap');
        }
    }

    function goToStap(stapNum) {
        if (stapNum >= 1 && stapNum <= 6) {
            currentStap = stapNum;
            updateStapDisplay();
            addLog('info', 'Naar stap ' + currentStap + ' gegaan');
        }
    }

    // Stap 1: Zaak Aanmaken
    window.createZaak = async function() {
        const zaaktype = document.getElementById('zaaktype').value;
        const omschrijving = document.getElementById('zaak-omschrijving').value;
        const bsn = document.getElementById('zaak-bsn').value;

        if (!zaaktype || !omschrijving) {
            showResult('zaak-result', 'error', 'Vul alle verplichte velden in');
            return;
        }

        addLog('info', 'Zaak aanmaken: ' + zaaktype);

        try {
            // ZGW-compliant zaak data
            const now = new Date();
            const today = now.toISOString().split('T')[0];
            const nowISO = now.toISOString();
            
            const zaakData = {
                identificatie: 'ZAAK-' + Date.now(),
                bronorganisatie: '123456789',
                zaaktype: 'https://catalogi.nl/api/v1/zaaktypen/' + zaaktype,
                registratiedatum: nowISO,
                startdatum: today,
                status: 'https://catalogi.nl/api/v1/statussen/open',
                omschrijving: omschrijving,
                verantwoordelijkeOrganisatie: '123456789'
            };

            if (bsn) {
                zaakData.betrokkeneIdentificaties = [{
                    identificatie: bsn,
                    type: 'natuurlijk_persoon'
                }];
            }

            // Use relative URL (Nextcloud routing)
            const apiUrl = buildUrl('/apps/openregister/zgw/zaken');
            console.log('Creating zaak at:', apiUrl);
            console.log('Zaak data:', zaakData);
            
            const response = await fetch(apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'OCS-APIRequest': 'true'
                },
                credentials: 'include',
                body: JSON.stringify(zaakData)
            });
            
            console.log('Response status:', response.status);
            console.log('Response URL:', response.url);

            const responseText = await response.text();
            
            if (!response.ok) {
                // Check if it's HTML (error page)
                if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html')) {
                    showResult('zaak-result', 'error', 'Fout: Server returned HTML (mogelijk 404 of authenticatie probleem). Status: ' + response.status);
                    addLog('error', 'Zaak aanmaken gefaald: Server returned HTML. Status: ' + response.status + ', URL: /apps/openregister/zgw/zaken');
                    return;
                }
            }
            
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                showResult('zaak-result', 'error', 'Fout: Server returned non-JSON. Status: ' + response.status + ', Response: ' + responseText.substring(0, 200));
                addLog('error', 'Zaak aanmaken gefaald: Invalid JSON response. Status: ' + response.status);
                return;
            }

            if (response.ok) {
                // Extract UUID from URL or use identificatie
                const zaakId = result.url ? result.url.split('/').pop() : (result.identificatie || result.uuid || result.id);
                currentZaakId = zaakId;
                showResult('zaak-result', 'success', 'Zaak aangemaakt! Zaak ID: ' + currentZaakId);
                addLog('success', 'Zaak aangemaakt met ID: ' + currentZaakId);
                setTimeout(function() { nextStap(); }, 2000);
            } else {
                const errorMsg = result.detail || result.error || JSON.stringify(result);
                showResult('zaak-result', 'error', 'Fout: ' + errorMsg);
                addLog('error', 'Zaak aanmaken gefaald: ' + errorMsg);
            }
        } catch (error) {
            showResult('zaak-result', 'error', 'Fout: ' + error.message);
            addLog('error', 'Zaak aanmaken error: ' + error.message);
        }
    };

    // Stap 2: Persoon Opzoeken
    window.searchPerson = async function() {
        const bsn = document.getElementById('search-bsn').value;
        const achternaam = document.getElementById('search-achternaam').value;

        if (!bsn && !achternaam) {
            showResult('person-result', 'error', 'Vul BSN of achternaam in');
            return;
        }

        addLog('info', 'Persoon zoeken: ' + (bsn || achternaam));

        try {
            let url = buildUrl('/apps/openregister/ingeschrevenpersonen');
            if (bsn) {
                url = buildUrl('/apps/openregister/ingeschrevenpersonen/' + bsn);
            } else {
                url += '?achternaam=' + encodeURIComponent(achternaam);
            }

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'OCS-APIRequest': 'true'
                },
                credentials: 'include'
            });
            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                result = { detail: 'Invalid JSON response: ' + responseText.substring(0, 100) };
            }

            if (response.ok) {
                if (bsn) {
                    displayPersonDetails(result);
                    currentBsn = bsn;
                } else {
                    const personen = result._embedded && result._embedded.ingeschrevenpersonen ? result._embedded.ingeschrevenpersonen : [];
                    if (personen.length > 0) {
                        displayPersonDetails(personen[0]);
                        currentBsn = personen[0].burgerservicenummer;
                    } else {
                        showResult('person-result', 'info', 'Geen personen gevonden');
                    }
                }
                addLog('success', 'Persoon gevonden: ' + currentBsn);
                setTimeout(function() { nextStap(); }, 2000);
            } else {
                showResult('person-result', 'error', 'Fout: ' + (result.detail || JSON.stringify(result)));
                addLog('error', 'Persoon zoeken gefaald: ' + (result.detail || JSON.stringify(result)));
            }
        } catch (error) {
            showResult('person-result', 'error', 'Fout: ' + error.message);
            addLog('error', 'Persoon zoeken error: ' + error.message);
        }
    };

    function displayPersonDetails(person) {
        const detailsDiv = document.getElementById('person-details');
        detailsDiv.classList.add('show');
        
        const voornamen = person.naam && person.naam.voornamen ? person.naam.voornamen.join(' ') : '';
        const voorvoegsel = person.naam && person.naam.voorvoegsel ? person.naam.voorvoegsel : '';
        const geslachtsnaam = person.naam && person.naam.geslachtsnaam ? person.naam.geslachtsnaam : '';
        const naam = voornamen + ' ' + voorvoegsel + ' ' + geslachtsnaam;
        
        detailsDiv.innerHTML = 
            '<h3>Persoonsgegevens</h3>' +
            '<div class="detail-row">' +
                '<div class="detail-label">BSN:</div>' +
                '<div class="detail-value">' + (person.burgerservicenummer || 'N/A') + '</div>' +
            '</div>' +
            '<div class="detail-row">' +
                '<div class="detail-label">Naam:</div>' +
                '<div class="detail-value">' + naam + '</div>' +
            '</div>' +
            '<div class="detail-row">' +
                '<div class="detail-label">Geboortedatum:</div>' +
                '<div class="detail-value">' + (person.geboorte && person.geboorte.datum && person.geboorte.datum.datum ? person.geboorte.datum.datum : 'N/A') + '</div>' +
            '</div>' +
            '<div class="detail-row">' +
                '<div class="detail-label">Geslacht:</div>' +
                '<div class="detail-value">' + (person.geslachtsaanduiding || 'N/A') + '</div>' +
            '</div>' +
            '<div class="detail-row">' +
                '<div class="detail-label">Adres:</div>' +
                '<div class="detail-value">' + 
                    (person.verblijfplaats && person.verblijfplaats.straatnaam ? person.verblijfplaats.straatnaam : '') + ' ' +
                    (person.verblijfplaats && person.verblijfplaats.huisnummer ? person.verblijfplaats.huisnummer : '') + ', ' +
                    (person.verblijfplaats && person.verblijfplaats.postcode ? person.verblijfplaats.postcode : '') + ' ' +
                    (person.verblijfplaats && person.verblijfplaats.woonplaatsnaam ? person.verblijfplaats.woonplaatsnaam : '') +
                '</div>' +
            '</div>';
    }

    // Stap 3: Mutatie Indienen
    window.updateMutatieForm = function() {
        const mutatieType = document.getElementById('mutatie-type').value;
        const formDiv = document.getElementById('mutatie-form');
        
        if (!mutatieType) {
            formDiv.innerHTML = '';
            return;
        }

        addLog('info', 'Mutatie formulier voor: ' + mutatieType);

        let formHTML = '';

        switch (mutatieType) {
            case 'verhuizing':
                formHTML = 
                    '<div class="form-group">' +
                        '<label>Nieuwe Straat:</label>' +
                        '<input type="text" id="mutatie-straat" class="form-control" placeholder="Hoofdstraat">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Huisnummer:</label>' +
                        '<input type="text" id="mutatie-huisnummer" class="form-control" placeholder="123">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Huisnummertoevoeging:</label>' +
                        '<input type="text" id="mutatie-toevoeging" class="form-control" placeholder="A">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Postcode:</label>' +
                        '<input type="text" id="mutatie-postcode" class="form-control" placeholder="1234AB" maxlength="6">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Woonplaats:</label>' +
                        '<input type="text" id="mutatie-woonplaats" class="form-control" placeholder="Amsterdam">' +
                    '</div>';
                break;
            case 'geboorte':
                formHTML = 
                    '<div class="form-group">' +
                        '<label>Geboortedatum:</label>' +
                        '<input type="date" id="mutatie-geboortedatum" class="form-control">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Geslacht:</label>' +
                        '<select id="mutatie-geslacht" class="form-control">' +
                            '<option value="man">Man</option>' +
                            '<option value="vrouw">Vrouw</option>' +
                            '<option value="onbekend">Onbekend</option>' +
                        '</select>' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Voornamen:</label>' +
                        '<input type="text" id="mutatie-voornamen" class="form-control" placeholder="Jan Pieter">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Geslachtsnaam:</label>' +
                        '<input type="text" id="mutatie-geslachtsnaam" class="form-control" placeholder="Jansen">' +
                    '</div>';
                break;
            case 'partnerschap':
                formHTML = 
                    '<div class="form-group">' +
                        '<label>Partner BSN:</label>' +
                        '<input type="text" id="mutatie-partner-bsn" class="form-control" placeholder="987654321" maxlength="9">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Datum Partnerschap:</label>' +
                        '<input type="date" id="mutatie-datum" class="form-control">' +
                    '</div>';
                break;
            case 'overlijden':
                formHTML = 
                    '<div class="form-group">' +
                        '<label>Overlijdensdatum:</label>' +
                        '<input type="date" id="mutatie-overlijdensdatum" class="form-control">' +
                    '</div>' +
                    '<div class="form-group">' +
                        '<label>Overlijdensplaats:</label>' +
                        '<input type="text" id="mutatie-overlijdensplaats" class="form-control" placeholder="Amsterdam">' +
                    '</div>';
                break;
        }

        formDiv.innerHTML = formHTML;
    };

    window.submitMutatie = async function() {
        const mutatieType = document.getElementById('mutatie-type').value;
        
        if (!mutatieType) {
            showResult('mutatie-result', 'error', 'Selecteer een mutatie type');
            return;
        }

        if (!currentBsn) {
            showResult('mutatie-result', 'error', 'Zoek eerst een persoon op');
            return;
        }

        addLog('info', 'Mutatie indienen: ' + mutatieType + ' voor BSN ' + currentBsn);

        try {
            let mutatieData = {
                bsn: currentBsn
            };

            let endpoint = '';
            
            switch (mutatieType) {
                case 'verhuizing':
                    endpoint = '/api/v1/relocations/intra';
                    mutatieData = {
                        declarant: { bsn: currentBsn },
                        newAddress: {
                            street: document.getElementById('mutatie-straat').value,
                            houseNumber: document.getElementById('mutatie-huisnummer').value,
                            houseNumberAddition: document.getElementById('mutatie-toevoeging').value,
                            postalCode: document.getElementById('mutatie-postcode').value,
                            city: document.getElementById('mutatie-woonplaats').value
                        }
                    };
                    break;
                case 'geboorte':
                    endpoint = '/api/v1/birth';
                    mutatieData = {
                        child: {
                            firstName: document.getElementById('mutatie-voornamen').value.split(' ')[0] || '',
                            lastName: document.getElementById('mutatie-geslachtsnaam').value,
                            birthDate: document.getElementById('mutatie-geboortedatum').value,
                            gender: document.getElementById('mutatie-geslacht').value
                        },
                        mother: {
                            bsn: currentBsn
                        }
                    };
                    break;
                case 'partnerschap':
                    endpoint = '/api/v1/commitment';
                    mutatieData = {
                        partner1: { bsn: currentBsn },
                        partner2: { bsn: document.getElementById('mutatie-partner-bsn').value },
                        commitmentDate: document.getElementById('mutatie-datum').value
                    };
                    break;
                case 'overlijden':
                    endpoint = '/api/v1/deaths/in-municipality';
                    mutatieData = {
                        person: {
                            bsn: currentBsn
                        },
                        deathDate: document.getElementById('mutatie-overlijdensdatum').value,
                        place: document.getElementById('mutatie-overlijdensplaats').value,
                        zaak_id: currentZaakId || null
                    };
                    break;
            }

            const response = await fetch(buildUrl('/apps/openregister' + endpoint), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'OCS-APIRequest': 'true'
                },
                credentials: 'include',
                body: JSON.stringify(mutatieData)
            });

            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                result = { detail: 'Invalid JSON response: ' + responseText.substring(0, 100) };
            }

            if (response.ok) {
                // Probeer meerdere velden zodat we altijd een ID hebben
                const mutatieIdFromUrl = result.url ? result.url.split('/').pop() : null;
                const locationHeader = response.headers.get('location');
                const mutatieIdFromLocation = locationHeader
                    ? locationHeader.split('/').filter(Boolean).pop()
                    : null;
                const mutatieIdFromNested = result.mutatie && (result.mutatie.id || result.mutatie.dossier_id);
                const mutatieIdCamel = result.mutatieId || result.dossierId || result.dossier_id;
                const mutatieIdSelf = result['@self'] && (result['@self'].id || result['@self'].uuid);
                
                currentMutatieId = result.mutatie_id 
                    || result.id 
                    || mutatieIdCamel
                    || mutatieIdSelf
                    || mutatieIdFromNested
                    || mutatieIdFromLocation
                    || mutatieIdFromUrl;

                if (!currentMutatieId) {
                    console.warn('Mutatie ingediend maar geen ID in response, volledige response:', result);
                    addLog('error', 'Mutatie ingediend, maar ID ontbreekt in response (controleer backend).');
                    addLog('error', 'Response body: ' + responseText.substring(0, 200));
                    // Fallback zodat de flow kan doorgaan, zelfs als backend geen ID terugstuurt
                    currentMutatieId = 'FALLBACK-' + Date.now();
                    addLog('info', 'Fallback mutatie ID gebruikt: ' + currentMutatieId);
                } else {
                    console.log('Mutatie ID gevonden:', currentMutatieId);
                }
                const mutatieMessage = currentMutatieId 
                    ? 'Mutatie ingediend! Mutatie ID: ' + currentMutatieId
                    : 'Mutatie ingediend, maar ID niet gevonden in response';
                showResult('mutatie-result', 'success', mutatieMessage);
                addLog('success', mutatieMessage);
                displayValidatie(result);
                setTimeout(function() { nextStap(); }, 2000);
            } else {
                showResult('mutatie-result', 'error', 'Fout: ' + (result.detail || JSON.stringify(result)));
                addLog('error', 'Mutatie indienen gefaald: ' + (result.detail || JSON.stringify(result)));
                displayValidatie(result, false);
            }
        } catch (error) {
            showResult('mutatie-result', 'error', 'Fout: ' + error.message);
            addLog('error', 'Mutatie indienen error: ' + error.message);
        }
    };

    // Stap 4: Validatie
    function displayValidatie(result, success) {
        success = success !== false;
        const validatieDiv = document.getElementById('validatie-result');
        const detailsDiv = document.getElementById('validatie-details');
        const nextButton = document.getElementById('btn-next-to-documenten');
        
        if (success) {
            validatieDiv.className = 'validatie-box success';
            validatieDiv.innerHTML = '<h3>Mutatie gevalideerd en geaccepteerd</h3>';
            // Toon knop om door te gaan naar stap 5
            if (nextButton) {
                nextButton.style.display = 'inline-block';
            }
        } else {
            validatieDiv.className = 'validatie-box error';
            validatieDiv.innerHTML = '<h3>Validatie gefaald</h3>';
            // Verberg knop bij fout
            if (nextButton) {
                nextButton.style.display = 'none';
            }
        }

        if (result.errors && result.errors.length > 0) {
            let errorsHTML = '<h4>Validatie Fouten:</h4>';
            result.errors.forEach(function(error) {
                errorsHTML += 
                    '<div class="validatie-item error">' +
                        '<span class="icon"></span>' +
                        '<span><strong>' + (error.field || 'Algemeen') + ':</strong> ' + (error.message || error) + '</span>' +
                    '</div>';
            });
            detailsDiv.innerHTML = errorsHTML;
        } else {
            detailsDiv.innerHTML = '<div class="validatie-item success"><span class="icon"></span><span>Geen validatie fouten</span></div>';
        }
    }

    // Stap 5: Documenten
    window.uploadDocument = async function() {
        const fileInput = document.getElementById('document-upload');
        const documentType = document.getElementById('document-type').value;

        if (!fileInput.files || fileInput.files.length === 0) {
            showResult('document-result', 'error', 'Selecteer een bestand');
            return;
        }

        if (!currentZaakId) {
            showResult('document-result', 'error', 'Maak eerst een zaak aan');
            return;
        }

        addLog('info', 'Document uploaden: ' + fileInput.files[0].name);

        try {
            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('zaak_id', currentZaakId);
            formData.append('document_type', documentType);
            formData.append('titel', fileInput.files[0].name);
            formData.append('zaak', currentZaakId);

            const response = await fetch(buildUrl('/apps/openregister/zgw/documenten'), {
                method: 'POST',
                headers: {
                    'OCS-APIRequest': 'true'
                },
                credentials: 'include',
                body: formData
            });

            const responseText = await response.text();
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                result = { detail: 'Invalid JSON response: ' + responseText.substring(0, 100) };
            }

            console.log('Document upload response status:', response.status);
            console.log('Document upload response.ok:', response.ok);
            console.log('Document upload result:', result);
            console.log('Current stap voor upload:', currentStap);
            console.log('Response text (first 200 chars):', responseText.substring(0, 200));

            // Accepteer zowel 200 als 201 als succesvol
            if (response.ok || response.status === 200 || response.status === 201) {
                console.log('Response is OK (status:', response.status, '), toon knop...');
                currentDocumenten.push(result);
                const documentId = result.document_id || result.id || result.uuid || (result['@self'] && result['@self'].uuid);
                showResult('document-result', 'success', 'Document geüpload! Document ID: ' + documentId);
                addLog('success', 'Document geüpload: ' + fileInput.files[0].name);
                displayDocumentList();
                // Ga automatisch door naar stap 6 (Status)
                if (currentStap < 6) {
                    goToStap(6);
                }
                
                // Toon knop om naar stap 6 te gaan na succesvolle upload
                // Gebruik setTimeout om ervoor te zorgen dat DOM updates zijn doorgevoerd
                setTimeout(function() {
                    const btnNextToStatus = document.getElementById('btn-next-to-status');
                    console.log('Knop element gevonden:', btnNextToStatus);
                    if (btnNextToStatus) {
                        console.log('Knop display voor:', btnNextToStatus.style.display);
                        // Gebruik !important via setProperty om CSS te overschrijven
                        btnNextToStatus.style.setProperty('display', 'inline-block', 'important');
                        // Ook via style.display voor browsers die setProperty niet ondersteunen
                        btnNextToStatus.style.display = 'inline-block';
                        // Verwijder eventuele verborgen class
                        btnNextToStatus.classList.remove('hidden', 'd-none');
                        btnNextToStatus.classList.add('visible');
                        // Zorg dat de knop zichtbaar is
                        btnNextToStatus.removeAttribute('hidden');
                        btnNextToStatus.setAttribute('aria-hidden', 'false');
                        console.log('Knop display na:', btnNextToStatus.style.display);
                        console.log('Knop computed style na:', window.getComputedStyle(btnNextToStatus).display);
                        addLog('info', 'Document succesvol geüpload. Klik op de groene knop om naar Status Overzicht te gaan.');
                    } else {
                        console.error('Knop btn-next-to-status niet gevonden in DOM!');
                        addLog('error', 'Knop niet gevonden - controleer of de knop in de HTML staat');
                    }
                }, 100);
            } else {
                console.error('Response is NIET OK. Status:', response.status, 'OK:', response.ok);
                showResult('document-result', 'error', 'Fout: ' + (result.detail || JSON.stringify(result)) + ' (Status: ' + response.status + ')');
                addLog('error', 'Document upload gefaald: ' + (result.detail || JSON.stringify(result)) + ' (Status: ' + response.status + ')');
            }
        } catch (error) {
            showResult('document-result', 'error', 'Fout: ' + error.message);
            addLog('error', 'Document upload error: ' + error.message);
        }
    };

    function displayDocumentList() {
        const listDiv = document.getElementById('document-list');
        if (currentDocumenten.length === 0) {
            listDiv.innerHTML = '<p>Geen documenten geüpload</p>';
            // Verberg knop als er geen documenten zijn
            const btnNextToStatus = document.getElementById('btn-next-to-status');
            if (btnNextToStatus) {
                btnNextToStatus.style.display = 'none';
            }
            return;
        }

        let html = '<h4>Geüploade Documenten:</h4>';
        currentDocumenten.forEach(function(doc) {
            html += 
                '<div class="document-item">' +
                    '<div class="icon"></div>' +
                    '<div class="info">' +
                        '<div class="title">' + (doc.titel || doc.bestandsnaam || 'Document') + '</div>' +
                        '<div class="meta">Type: ' + (doc.document_type || 'Onbekend') + ' | Grootte: ' + (doc.bestandsgrootte || 'N/A') + ' bytes</div>' +
                    '</div>' +
                '</div>';
        });
        listDiv.innerHTML = html;
        
        // Zorg dat de knop ALTIJD zichtbaar is als er documenten zijn
        const btnNextToStatus = document.getElementById('btn-next-to-status');
        console.log('displayDocumentList: Knop element gevonden:', btnNextToStatus);
        console.log('displayDocumentList: Aantal documenten:', currentDocumenten.length);
        if (btnNextToStatus) {
            console.log('displayDocumentList: Toon knop...');
            // Gebruik meerdere methoden om zeker te zijn dat de knop zichtbaar wordt
            btnNextToStatus.style.setProperty('display', 'inline-block', 'important');
            btnNextToStatus.style.display = 'inline-block';
            btnNextToStatus.style.visibility = 'visible';
            btnNextToStatus.style.opacity = '1';
            btnNextToStatus.removeAttribute('hidden');
            btnNextToStatus.setAttribute('aria-hidden', 'false');
            btnNextToStatus.classList.remove('hidden', 'd-none');
            console.log('displayDocumentList: Knop display na:', btnNextToStatus.style.display);
            console.log('displayDocumentList: Knop computed style:', window.getComputedStyle(btnNextToStatus).display);
            addLog('info', 'Klik op de groene knop hieronder om naar Status Overzicht te gaan.');
        } else {
            console.error('displayDocumentList: Knop btn-next-to-status niet gevonden!');
            addLog('error', 'Knop niet gevonden in DOM');
        }
    }

    // Stap 6: Status
    window.refreshStatus = async function() {
        addLog('info', 'Status vernieuwen');

        const overviewDiv = document.getElementById('status-overview');
        let html = '';
        let mutatieStatusCard = '';

        if (currentZaakId) {
            try {
                const response = await fetch(buildUrl('/apps/openregister/zgw/zaken/' + currentZaakId), {
                    headers: {
                        'Accept': 'application/json',
                        'OCS-APIRequest': 'true'
                    },
                    credentials: 'include'
                });
                let zaak = null;
                if (response.ok) {
                    const responseText = await response.text();
                    try {
                        zaak = JSON.parse(responseText);
                    } catch (e) {
                        zaak = null;
                    }
                }
                
                html += 
                    '<div class="status-card">' +
                        '<h4>Zaak Status</h4>' +
                        '<div class="status-value">' + (zaak && zaak.status ? zaak.status : 'Onbekend') + '</div>' +
                        '<div class="status-label">Zaak ID: ' + currentZaakId + '</div>' +
                    '</div>';
            } catch (error) {
                addLog('error', 'Zaak status ophalen gefaald: ' + error.message);
            }
        }

        if (currentMutatieId) {
            try {
                const response = await fetch(buildUrl('/apps/openregister/api/v1/mutaties/' + currentMutatieId), {
                    headers: {
                        'Accept': 'application/json',
                        'OCS-APIRequest': 'true'
                    },
                    credentials: 'include'
                });
                if (response.ok) {
                    const text = await response.text();
                    let mutatie = null;
                    try {
                        mutatie = JSON.parse(text);
                    } catch (e) {
                        mutatie = null;
                    }
                    mutatieStatusCard =
                        '<div class="status-card">' +
                            '<h4>Mutatie Status</h4>' +
                            '<div class="status-value">' + (mutatie && mutatie.status ? mutatie.status : 'Ingediend') + '</div>' +
                            '<div class="status-label">Mutatie ID: ' + currentMutatieId + '</div>' +
                            '<div class="status-label">Persoon status: ' + (mutatie && mutatie.persoon_status ? mutatie.persoon_status : 'onbekend') + '</div>' +
                        '</div>';
                } else {
                    mutatieStatusCard =
                        '<div class="status-card">' +
                            '<h4>Mutatie Status</h4>' +
                            '<div class="status-value">Onbekend</div>' +
                            '<div class="status-label">Mutatie ID: ' + currentMutatieId + '</div>' +
                            '<div class="status-label">Kon status niet ophalen (HTTP ' + response.status + ')</div>' +
                        '</div>';
                }
            } catch (error) {
                addLog('error', 'Mutatie status ophalen gefaald: ' + error.message);
            }
        } else {
            mutatieStatusCard =
                '<div class="status-card">' +
                    '<h4>Mutatie Status</h4>' +
                    '<div class="status-value">Niet ingediend</div>' +
                    '<div class="status-label">Mutatie ID: N/A</div>' +
                '</div>';
        }

        html += mutatieStatusCard;

        html += 
            '<div class="status-card">' +
                '<h4>Documenten</h4>' +
                '<div class="status-value">' + currentDocumenten.length + '</div>' +
                '<div class="status-label">Documenten geüpload</div>' +
            '</div>';

        overviewDiv.innerHTML = html;
    };

    // Log Management
    function addLog(level, message) {
        const logContent = document.getElementById('proces-log-content');
        if (!logContent) return;
        
        const timestamp = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.className = 'log-entry';
        entry.innerHTML = 
            '<span class="timestamp">[' + timestamp + ']</span> ' +
            '<span class="level ' + level + '">[' + level.toUpperCase() + ']</span> ' +
            '<span class="message">' + message + '</span>';
        logContent.appendChild(entry);
        logContent.scrollTop = logContent.scrollHeight;
    }

    window.clearLog = function() {
        const logContent = document.getElementById('proces-log-content');
        if (logContent) {
            logContent.innerHTML = '';
            addLog('info', 'Log gewist');
        }
    };

    // Helper Functions
    function showResult(elementId, type, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.className = 'result-box ' + type;
            element.textContent = message;
        }
    }

    // Setup Event Listeners (CSP-compliant)
    function setupEventListeners() {
        console.log('Setting up event listeners...');
        
        // Helper function to safely add event listener
        function safeAddEventListener(element, event, handler) {
            if (element && typeof handler === 'function') {
                element.addEventListener(event, handler, false);
                return true;
            }
            return false;
        }
        
        // Zaak aanmaken button
        var btnCreateZaak = document.getElementById('btn-create-zaak');
        if (btnCreateZaak) {
            safeAddEventListener(btnCreateZaak, 'click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Zaak aanmaken clicked');
                if (typeof window.createZaak === 'function') {
                    window.createZaak();
                } else {
                    console.error('createZaak function not found');
                }
            });
        }

        // Persoon zoeken button
        var btnSearchPerson = document.getElementById('btn-search-person');
        safeAddEventListener(btnSearchPerson, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.searchPerson === 'function') {
                window.searchPerson();
            }
        });

        // Mutatie type select
        var mutatieTypeSelect = document.getElementById('mutatie-type');
        safeAddEventListener(mutatieTypeSelect, 'change', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.updateMutatieForm === 'function') {
                window.updateMutatieForm();
            }
        });

        // Mutatie indienen button
        var btnSubmitMutatie = document.getElementById('btn-submit-mutatie');
        safeAddEventListener(btnSubmitMutatie, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.submitMutatie === 'function') {
                window.submitMutatie();
            }
        });

        // Document upload button
        var btnUploadDocument = document.getElementById('btn-upload-document');
        safeAddEventListener(btnUploadDocument, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.uploadDocument === 'function') {
                window.uploadDocument();
            }
        });

        // Status refresh button
        var btnRefreshStatus = document.getElementById('btn-refresh-status');
        safeAddEventListener(btnRefreshStatus, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.refreshStatus === 'function') {
                window.refreshStatus();
            }
        });

        // Next to documenten button (Stap 4 -> Stap 5)
        var btnNextToDocumenten = document.getElementById('btn-next-to-documenten');
        safeAddEventListener(btnNextToDocumenten, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (currentStap === 4) {
                nextStap();
                addLog('info', 'Doorgaan naar stap 5: Documenten');
            }
        });

        // Next to status button (Stap 5 -> Stap 6)
        var btnNextToStatus = document.getElementById('btn-next-to-status');
        safeAddEventListener(btnNextToStatus, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (currentStap === 5) {
                nextStap();
                addLog('info', 'Doorgaan naar stap 6: Status Overzicht');
                // Laad status automatisch bij openen van stap 6
                if (typeof window.refreshStatus === 'function') {
                    setTimeout(function() { 
                        window.refreshStatus(); 
                        addLog('info', 'Status overzicht geladen');
                    }, 500);
                }
            }
        });

        // Clear log button
        var btnClearLog = document.getElementById('btn-clear-log');
        safeAddEventListener(btnClearLog, 'click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (typeof window.clearLog === 'function') {
                window.clearLog();
            }
        });
        
        console.log('Event listeners setup complete');
    }

    function tryInit() {
        var logContent = document.getElementById('proces-log-content');
        var btnCreateZaak = document.getElementById('btn-create-zaak');
        if (logContent && btnCreateZaak) {
            init();
        } else {
            setTimeout(tryInit, 100);
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(tryInit, 100);
        });
    } else {
        setTimeout(tryInit, 100);
    }

    // Fallback initialization
    setTimeout(function() {
        if (!initialized) {
            var logContent = document.getElementById('proces-log-content');
            var btnCreateZaak = document.getElementById('btn-create-zaak');
            if (logContent && btnCreateZaak) {
                initialized = false;
                init();
            }
        }
    }, 1000);
})();

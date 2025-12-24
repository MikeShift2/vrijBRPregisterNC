(function() {
    'use strict';
    
    console.log('Prefill test script executing...');
    var API_BASE = window.location.origin + '/apps/openregister';
    var initialized = false;
    
    function showLoading() {
        var loading = document.getElementById('prefill-loading');
        if (loading) loading.style.display = 'flex';
        var error = document.getElementById('prefill-error');
        if (error) error.style.display = 'none';
    }
    
    function hideLoading() {
        var loading = document.getElementById('prefill-loading');
        if (loading) loading.style.display = 'none';
    }
    
    function showError(message) {
        var error = document.getElementById('prefill-error');
        if (error) {
            error.textContent = message;
            error.style.display = 'block';
        }
        hideLoading();
    }
    
    function hideError() {
        var error = document.getElementById('prefill-error');
        if (error) error.style.display = 'none';
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    function formatDate(dateStr) {
        if (!dateStr) return '';
        if (dateStr.match(/^\d{4}-\d{2}-\d{2}$/)) {
            return dateStr;
        }
        if (dateStr.match(/^\d{8}$/)) {
            return dateStr.substring(0, 4) + '-' + dateStr.substring(4, 6) + '-' + dateStr.substring(6, 8);
        }
        return dateStr;
    }
    
    function searchPersons(searchTerm) {
        console.log('searchPersons called with:', searchTerm);
        if (!searchTerm || searchTerm.trim() === '') {
            console.log('Empty search term');
            showError('Voer een BSN of achternaam in');
            return;
        }
        
        showLoading();
        hideError();
        
        var searchParams = {};
        if (/^\d{9}$/.test(searchTerm.trim())) {
            searchParams.bsn = searchTerm.trim();
        } else {
            searchParams.achternaam = searchTerm.trim();
        }
        
        var url = API_BASE + '/ingeschrevenpersonen?_limit=20';
        for (var key in searchParams) {
            url += '&' + encodeURIComponent(key) + '=' + encodeURIComponent(searchParams[key]);
        }
        
        console.log('Fetching URL:', url);
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
            hideLoading();
            displayResults(data);
        })
        .catch(function(error) {
            showError('Fout bij zoeken: ' + error.message);
        });
    }
    
    function displayResults(data) {
        var resultsContainer = document.getElementById('prefill-results');
        var resultsList = document.getElementById('prefill-results-list');
        
        if (!resultsContainer || !resultsList) return;
        
        var persons = data._embedded && data._embedded.ingeschrevenpersonen ? data._embedded.ingeschrevenpersonen : [];
        
        if (persons.length === 0) {
            showError('Geen resultaten gevonden');
            resultsContainer.style.display = 'block';
            return;
        }
        
        resultsContainer.style.display = 'block';
        hideError();
        
        var html = '';
        persons.forEach(function(person) {
            var naam = person.naam || {};
            var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
            var geslachtsnaam = naam.geslachtsnaam || '';
            var voorvoegsel = naam.voorvoegsel || '';
            var volledigeNaam = voorvoegsel ? geslachtsnaam + ', ' + voornamen + ' ' + voorvoegsel : geslachtsnaam + ', ' + voornamen;
            
            html += '<div class="result-item" data-person=\'' + JSON.stringify(person).replace(/'/g, "&#39;") + '\'>';
            html += '<h4>' + escapeHtml(volledigeNaam || 'Onbekend') + '</h4>';
            if (person.burgerservicenummer) {
                html += '<p><strong>BSN:</strong> ' + escapeHtml(person.burgerservicenummer) + '</p>';
            }
            if (person.aNummer || person.administratienummer) {
                html += '<p><strong>A-nummer:</strong> ' + escapeHtml(person.aNummer || person.administratienummer) + '</p>';
            }
            html += '<button class="prefill-button" data-person=\'' + JSON.stringify(person).replace(/'/g, "&#39;") + '\'>Prefill formulier</button>';
            html += '</div>';
        });
        
        resultsList.innerHTML = html;
        
        // Event delegation voor prefill buttons
        resultsList.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('prefill-button')) {
                e.preventDefault();
                e.stopPropagation();
                console.log('Prefill button clicked');
                var personData = e.target.getAttribute('data-person');
                console.log('Person data:', personData ? 'Found' : 'Not found');
                if (personData) {
                    try {
                        var person = JSON.parse(personData.replace(/&#39;/g, "'"));
                        console.log('Person parsed successfully:', person);
                        prefillForm(person);
                    } catch (err) {
                        console.error('Fout bij parsen persoon data:', err);
                        showError('Fout bij prefill: ' + err.message);
                    }
                } else {
                    console.error('Geen person data gevonden in button');
                }
            }
        });
    }
    
    function prefillForm(person) {
        console.log('prefillForm called with person:', person);
        var naam = person.naam || {};
        var geboorte = person.geboorte || {};
        var verblijfplaats = person.verblijfplaats || {};
        var bsn = person.burgerservicenummer;
        
        console.log('BSN:', bsn);
        console.log('Naam:', naam);
        
        // Sluit results overlay EERST
        var resultsOverlay = document.getElementById('prefill-results');
        if (resultsOverlay) {
            resultsOverlay.style.display = 'none';
            console.log('Results overlay gesloten');
        }
        
        // Toon person display container
        var personDisplay = document.getElementById('person-display');
        console.log('Person display element:', personDisplay);
        if (!personDisplay) {
            console.error('Person display container niet gevonden!');
            alert('Fout: Person display container niet gevonden. Controleer de HTML structuur.');
            return; // Stop als container niet bestaat
        }
        
        // Verwijder inline style die display: none bevat en forceer display
        personDisplay.removeAttribute('style');
        personDisplay.style.cssText = 'display: block !important; visibility: visible !important; opacity: 1 !important;';
        personDisplay.classList.add('visible');
        
        console.log('Person display container getoond');
        console.log('Display style:', personDisplay.style.display);
        console.log('Computed display:', window.getComputedStyle(personDisplay).display);
        
        // Scroll naar person display
        setTimeout(function() {
            if (personDisplay) {
                personDisplay.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
        
        // Format naam voor display
        var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
        var voorvoegsel = naam.voorvoegsel || '';
        var geslachtsnaam = naam.geslachtsnaam || '';
        var volledigeNaam = voorvoegsel ? geslachtsnaam + ', ' + voornamen + ' ' + voorvoegsel : geslachtsnaam + ', ' + voornamen;
        
        // Format geboortedatum met leeftijd
        var geboortedatumStr = '-';
        var leeftijd = '';
        if (geboorte.datum && geboorte.datum.datum) {
            var geboorteDate = new Date(geboorte.datum.datum);
            var nu = new Date();
            var jaren = nu.getFullYear() - geboorteDate.getFullYear();
            var maandDiff = nu.getMonth() - geboorteDate.getMonth();
            if (maandDiff < 0 || (maandDiff === 0 && nu.getDate() < geboorteDate.getDate())) {
                jaren--;
            }
            var dag = String(geboorteDate.getDate()).padStart(2, '0');
            var maand = String(geboorteDate.getMonth() + 1).padStart(2, '0');
            geboortedatumStr = dag + '-' + maand + '-' + geboorteDate.getFullYear();
            leeftijd = ' (' + jaren + ' jaar)';
        }
        
        // Format adres
        var adresStr = '-';
        if (verblijfplaats && (verblijfplaats.straatnaam || verblijfplaats.huisnummer)) {
            var adresParts = [];
            if (verblijfplaats.straatnaam) adresParts.push(verblijfplaats.straatnaam);
            if (verblijfplaats.huisnummer) adresParts.push(verblijfplaats.huisnummer);
            if (verblijfplaats.huisnummertoevoeging) adresParts.push(verblijfplaats.huisnummertoevoeging);
            if (verblijfplaats.postcode) adresParts.push(verblijfplaats.postcode);
            if (verblijfplaats.woonplaatsnaam || verblijfplaats.woonplaats) {
                adresParts.push('(' + (verblijfplaats.woonplaatsnaam || verblijfplaats.woonplaats) + ')');
            }
            adresStr = adresParts.join(' ');
        }
        
        // Format geslachtsaanduiding
        var geslachtStr = person.geslachtsaanduiding || '-';
        if (geslachtStr === 'man') geslachtStr = 'Man';
        if (geslachtStr === 'vrouw') geslachtStr = 'Vrouw';
        if (geslachtStr === 'onbekend') geslachtStr = 'Onbekend';
        
        // Update Person Overview Card
        var personNameEl = document.getElementById('display-person-name');
        if (personNameEl) personNameEl.textContent = volledigeNaam || '-';
        
        var personGenderEl = document.getElementById('display-person-gender');
        if (personGenderEl) personGenderEl.textContent = geslachtStr;
        
        var personBirthEl = document.getElementById('display-person-birth');
        if (personBirthEl) personBirthEl.textContent = geboortedatumStr + leeftijd;
        
        var personAddressEl = document.getElementById('display-person-address');
        if (personAddressEl) personAddressEl.textContent = adresStr;
        
        var personBsnEl = document.getElementById('display-person-bsn');
        if (personBsnEl) personBsnEl.textContent = bsn || '-';
        
        var personAnummerEl = document.getElementById('display-person-anummer');
        if (personAnummerEl) personAnummerEl.textContent = person.aNummer || '-';
        
        var personStatusEl = document.getElementById('display-person-status');
        if (personStatusEl) {
            // Check of er een partner is (wordt later ingevuld)
            personStatusEl.textContent = '-';
        }
        
        // Update Details Panel - Persoon
        var detailAnummerEl = document.getElementById('detail-anummer');
        if (detailAnummerEl) detailAnummerEl.textContent = person.aNummer || '-';
        
        var detailBsnEl = document.getElementById('detail-bsn');
        if (detailBsnEl) detailBsnEl.textContent = bsn || '-';
        
        // Update Inschrijving Details (categorie 01)
        var detailInschrijvingAnummerEl = document.getElementById('detail-inschrijving-anummer');
        if (detailInschrijvingAnummerEl) detailInschrijvingAnummerEl.textContent = person.aNummer || '-';
        
        var detailInschrijvingBsnEl = document.getElementById('detail-inschrijving-bsn');
        if (detailInschrijvingBsnEl) detailInschrijvingBsnEl.textContent = bsn || '-';
        
        var detailVoornamenEl = document.getElementById('detail-voornamen');
        if (detailVoornamenEl) detailVoornamenEl.textContent = voornamen || '-';
        
        var detailGeslachtsnaamEl = document.getElementById('detail-geslachtsnaam');
        if (detailGeslachtsnaamEl) detailGeslachtsnaamEl.textContent = geslachtsnaam || '-';
        
        var detailGeboortedatumEl = document.getElementById('detail-geboortedatum');
        if (detailGeboortedatumEl) detailGeboortedatumEl.textContent = geboortedatumStr || '-';
        
        var detailGeboorteplaatsEl = document.getElementById('detail-geboorteplaats');
        if (detailGeboorteplaatsEl) detailGeboorteplaatsEl.textContent = geboorte.plaats || '-';
        
        var detailGeboortelandEl = document.getElementById('detail-geboorteland');
        if (detailGeboortelandEl) detailGeboortelandEl.textContent = geboorte.land || '-';
        
        var detailGeslachtsaanduidingEl = document.getElementById('detail-geslachtsaanduiding');
        if (detailGeslachtsaanduidingEl) detailGeslachtsaanduidingEl.textContent = geslachtStr;
        
        // Update Persoon Details (categorie 02) - aanvullende velden
        // Deze worden later ingevuld via loadAdditionalCategoryData
        
        // Update Verblijfplaats Details
        var detailStraatnaamEl = document.getElementById('detail-straatnaam');
        if (detailStraatnaamEl) detailStraatnaamEl.textContent = verblijfplaats.straatnaam || '-';
        
        var detailHuisnummerEl = document.getElementById('detail-huisnummer');
        if (detailHuisnummerEl) detailHuisnummerEl.textContent = verblijfplaats.huisnummer || '-';
        
        // Format datum helper functie
        function formatDatum(datum) {
            if (!datum || datum === '-') return '-';
            if (typeof datum === 'string' && datum.includes('-')) {
                var parts = datum.split('-');
                if (parts.length === 3) {
                    return parts[2] + '-' + parts[1] + '-' + parts[0];
                }
            }
            return datum;
        }
        
        // 09.10 gemeente van inschrijving
        var detailVerblijfplaatsGemeenteInschrijvingEl = document.getElementById('detail-verblijfplaats-gemeente-inschrijving');
        if (detailVerblijfplaatsGemeenteInschrijvingEl) {
            detailVerblijfplaatsGemeenteInschrijvingEl.textContent = verblijfplaats.gemeenteVanInschrijving || verblijfplaats.gemeenteInschrijving || verblijfplaats.gemeente || '-';
        }
        
        // 09.11 code gemeente van inschrijving
        var detailVerblijfplaatsCodeGemeenteInschrijvingEl = document.getElementById('detail-verblijfplaats-code-gemeente-inschrijving');
        if (detailVerblijfplaatsCodeGemeenteInschrijvingEl) {
            detailVerblijfplaatsCodeGemeenteInschrijvingEl.textContent = verblijfplaats.codeGemeenteVanInschrijving || verblijfplaats.codeGemeenteInschrijving || verblijfplaats.gemeenteCode || '-';
        }
        
        // 09.20 datum inschrijving
        var detailVerblijfplaatsDatumInschrijvingEl = document.getElementById('detail-verblijfplaats-datum-inschrijving');
        if (detailVerblijfplaatsDatumInschrijvingEl) {
            var datumInschrijving = verblijfplaats.datumInschrijving?.datum || verblijfplaats.datumInschrijving || '-';
            detailVerblijfplaatsDatumInschrijvingEl.textContent = formatDatum(datumInschrijving);
        }
        
        // 10.10 functie adres
        var detailVerblijfplaatsFunctieAdresEl = document.getElementById('detail-verblijfplaats-functie-adres');
        if (detailVerblijfplaatsFunctieAdresEl) {
            detailVerblijfplaatsFunctieAdresEl.textContent = verblijfplaats.functieAdres || verblijfplaats.functie || '-';
        }
        
        // 10.20 gemeentedeel
        var detailVerblijfplaatsGemeentedeelEl = document.getElementById('detail-verblijfplaats-gemeentedeel');
        if (detailVerblijfplaatsGemeentedeelEl) {
            detailVerblijfplaatsGemeentedeelEl.textContent = verblijfplaats.gemeentedeel || '-';
        }
        
        // 10.30 datum aanvang adreshouding
        var detailVerblijfplaatsDatumAanvangAdreshoudingEl = document.getElementById('detail-verblijfplaats-datum-aanvang-adreshouding');
        if (detailVerblijfplaatsDatumAanvangAdreshoudingEl) {
            var datumAanvangAdreshouding = verblijfplaats.datumAanvangAdreshouding?.datum || verblijfplaats.datumAanvangAdreshouding || verblijfplaats.datumAanvangAdres?.datum || verblijfplaats.datumAanvangAdres || '-';
            detailVerblijfplaatsDatumAanvangAdreshoudingEl.textContent = formatDatum(datumAanvangAdreshouding);
        }
        
        // 11.10 straatnaam
        var detailVerblijfplaatsStraatnaamEl = document.getElementById('detail-verblijfplaats-straatnaam');
        if (detailVerblijfplaatsStraatnaamEl) {
            detailVerblijfplaatsStraatnaamEl.textContent = verblijfplaats.straatnaam || verblijfplaats.adres?.straatnaam || '-';
        }
        
        // 11.11 straatnaam (officieel)
        var detailVerblijfplaatsStraatnaamOfficieelEl = document.getElementById('detail-verblijfplaats-straatnaam-officieel');
        if (detailVerblijfplaatsStraatnaamOfficieelEl) {
            detailVerblijfplaatsStraatnaamOfficieelEl.textContent = verblijfplaats.straatnaamOfficieel || verblijfplaats.straatnaam || verblijfplaats.adres?.straatnaam || '-';
        }
        
        // 11.12 straatnaam (NEN)
        var detailVerblijfplaatsStraatnaamNenEl = document.getElementById('detail-verblijfplaats-straatnaam-nen');
        if (detailVerblijfplaatsStraatnaamNenEl) {
            detailVerblijfplaatsStraatnaamNenEl.textContent = verblijfplaats.straatnaamNen || verblijfplaats.straatnaam || verblijfplaats.adres?.straatnaam || '-';
        }
        
        // 11.15 openbare ruimte
        var detailVerblijfplaatsOpenbareRuimteEl = document.getElementById('detail-verblijfplaats-openbare-ruimte');
        if (detailVerblijfplaatsOpenbareRuimteEl) {
            detailVerblijfplaatsOpenbareRuimteEl.textContent = verblijfplaats.openbareRuimte || verblijfplaats.straatnaam || verblijfplaats.adres?.openbareRuimte || '-';
        }
        
        // 11.20 huisnummer
        var detailVerblijfplaatsHuisnummerEl = document.getElementById('detail-verblijfplaats-huisnummer');
        if (detailVerblijfplaatsHuisnummerEl) {
            detailVerblijfplaatsHuisnummerEl.textContent = verblijfplaats.huisnummer || verblijfplaats.adres?.huisnummer || '-';
        }
        
        // 11.60 postcode
        var detailVerblijfplaatsPostcodeEl = document.getElementById('detail-verblijfplaats-postcode');
        if (detailVerblijfplaatsPostcodeEl) {
            detailVerblijfplaatsPostcodeEl.textContent = verblijfplaats.postcode || verblijfplaats.adres?.postcode || '-';
        }
        
        // 11.70 woonplaatsnaam
        var detailVerblijfplaatsWoonplaatsnaamEl = document.getElementById('detail-verblijfplaats-woonplaatsnaam');
        if (detailVerblijfplaatsWoonplaatsnaamEl) {
            detailVerblijfplaatsWoonplaatsnaamEl.textContent = verblijfplaats.woonplaatsnaam || verblijfplaats.woonplaats || verblijfplaats.adres?.woonplaatsnaam || '-';
        }
        
        // 11.80 identificatie verblijfplaats
        var detailVerblijfplaatsIdentificatieVerblijfplaatsEl = document.getElementById('detail-verblijfplaats-identificatie-verblijfplaats');
        if (detailVerblijfplaatsIdentificatieVerblijfplaatsEl) {
            detailVerblijfplaatsIdentificatieVerblijfplaatsEl.textContent = verblijfplaats.identificatieVerblijfplaats || verblijfplaats.identificatie || verblijfplaats.adres?.identificatie || '-';
        }
        
        // 11.90 identificatiecode nummeraanduiding
        var detailVerblijfplaatsIdentificatiecodeNummeraanduidingEl = document.getElementById('detail-verblijfplaats-identificatiecode-nummeraanduiding');
        if (detailVerblijfplaatsIdentificatiecodeNummeraanduidingEl) {
            detailVerblijfplaatsIdentificatiecodeNummeraanduidingEl.textContent = verblijfplaats.identificatiecodeNummeraanduiding || verblijfplaats.nummeraanduiding || verblijfplaats.adres?.nummeraanduiding || '-';
        }
        
        // 14.10 land vanwaar ingeschreven
        var detailVerblijfplaatsLandVanwaarIngeschrevenEl = document.getElementById('detail-verblijfplaats-land-vanwaar-ingeschreven');
        if (detailVerblijfplaatsLandVanwaarIngeschrevenEl) {
            detailVerblijfplaatsLandVanwaarIngeschrevenEl.textContent = verblijfplaats.landVanwaarIngeschreven || verblijfplaats.landVanwaar || verblijfplaats.land || '-';
        }
        
        // 14.20 datum vestiging in Nederland
        var detailVerblijfplaatsDatumVestigingNederlandEl = document.getElementById('detail-verblijfplaats-datum-vestiging-nederland');
        if (detailVerblijfplaatsDatumVestigingNederlandEl) {
            var datumVestigingNederland = verblijfplaats.datumVestigingInNederland?.datum || verblijfplaats.datumVestigingInNederland || verblijfplaats.datumVestiging?.datum || verblijfplaats.datumVestiging || '-';
            detailVerblijfplaatsDatumVestigingNederlandEl.textContent = formatDatum(datumVestigingNederland);
        }
        
        // 72.10 omschrijving van de aangifte adreshouding
        var detailVerblijfplaatsOmschrijvingAangifteAdreshoudingEl = document.getElementById('detail-verblijfplaats-omschrijving-aangifte-adreshouding');
        if (detailVerblijfplaatsOmschrijvingAangifteAdreshoudingEl) {
            detailVerblijfplaatsOmschrijvingAangifteAdreshoudingEl.textContent = verblijfplaats.omschrijvingAangifteAdreshouding || verblijfplaats.omschrijvingAangifte || verblijfplaats.aangifteAdreshouding || '-';
        }
        
        // 85.10 ingangsdatum geldigheid
        var detailVerblijfplaatsIngangsdatumGeldigheidEl = document.getElementById('detail-verblijfplaats-ingangsdatum-geldigheid');
        if (detailVerblijfplaatsIngangsdatumGeldigheidEl) {
            var ingangsdatumGeldigheid = verblijfplaats.ingangsdatumGeldigheid?.datum || 
                                        verblijfplaats.ingangsdatumGeldigheid || 
                                        verblijfplaats.datumIngangGeldigheid?.datum ||
                                        verblijfplaats.datumIngangGeldigheid ||
                                        '-';
            detailVerblijfplaatsIngangsdatumGeldigheidEl.textContent = formatDatum(ingangsdatumGeldigheid);
        }
        
        // 85.11 volgcode geldigheid
        var detailVerblijfplaatsVolgcodeGeldigheidEl = document.getElementById('detail-verblijfplaats-volgcode-geldigheid');
        if (detailVerblijfplaatsVolgcodeGeldigheidEl) {
            var volgcodeGeldigheid = verblijfplaats.volgcodeGeldigheid || 
                                    verblijfplaats.volgcode || 
                                    '0';
            detailVerblijfplaatsVolgcodeGeldigheidEl.textContent = volgcodeGeldigheid;
        }
        
        // 86.10 datum van opneming
        var detailVerblijfplaatsDatumOpnemingEl = document.getElementById('detail-verblijfplaats-datum-opneming');
        if (detailVerblijfplaatsDatumOpnemingEl) {
            var datumOpneming = verblijfplaats.datumOpneming?.datum || 
                               verblijfplaats.datumOpneming || 
                               '-';
            detailVerblijfplaatsDatumOpnemingEl.textContent = formatDatum(datumOpneming);
        }
        
        // Functie om adresgegevens in te vullen
        function fillAddressFields(verblijfplaatsData) {
            var straatnaamInput = document.getElementById('form-straatnaam');
            if (straatnaamInput) straatnaamInput.value = verblijfplaatsData.straatnaam || '';
            
            var huisnummerInput = document.getElementById('form-huisnummer');
            if (huisnummerInput) huisnummerInput.value = verblijfplaatsData.huisnummer || '';
            
            var huisnummertoevoegingInput = document.getElementById('form-huisnummertoevoeging');
            if (huisnummertoevoegingInput) huisnummertoevoegingInput.value = verblijfplaatsData.huisnummertoevoeging || '';
            
            var postcodeInput = document.getElementById('form-postcode');
            if (postcodeInput) postcodeInput.value = verblijfplaatsData.postcode || '';
            
            var woonplaatsInput = document.getElementById('form-woonplaats');
            if (woonplaatsInput) woonplaatsInput.value = verblijfplaatsData.woonplaatsnaam || verblijfplaatsData.woonplaats || '';
        }
        
        // Als er al verblijfplaats data is, gebruik die
        if (verblijfplaats && (verblijfplaats.straatnaam || verblijfplaats.huisnummer || verblijfplaats.postcode)) {
            fillAddressFields(verblijfplaats);
        } else if (bsn) {
            // Haal volledige persoon data op (inclusief verblijfplaats als die er is)
            fetch(API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn), {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'OCS-APIRequest': 'true'
                },
                credentials: 'include'
            })
            .then(function(response) {
                if (!response.ok) {
                    console.warn('Kon persoon niet ophalen:', response.status);
                    return {};
                }
                return response.json();
            })
            .then(function(personData) {
                // Gebruik verblijfplaats uit persoon data als die er is
                if (personData.verblijfplaats) {
                    fillAddressFields(personData.verblijfplaats);
                    console.log('Verblijfplaats uit persoon data:', personData.verblijfplaats);
                } else {
                    // Als er geen verblijfplaats in persoon data is, probeer het endpoint
                    // (maar dit geeft mogelijk een timeout, dus we proberen het alleen als fallback)
                    console.log('Geen verblijfplaats in persoon data, probeer endpoint...');
                    fetch(API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn) + '/verblijfplaats', {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'OCS-APIRequest': 'true'
                        },
                        credentials: 'include',
                        signal: AbortSignal.timeout(5000) // 5 seconden timeout
                    })
                    .then(function(response) {
                        if (!response.ok) {
                            console.warn('Kon verblijfplaats endpoint niet ophalen:', response.status);
                            return {};
                        }
                        return response.json();
                    })
                    .then(function(verblijfplaatsData) {
                        if (verblijfplaatsData && (verblijfplaatsData.straatnaam || verblijfplaatsData.huisnummer || verblijfplaatsData.postcode)) {
                            fillAddressFields(verblijfplaatsData);
                            console.log('Verblijfplaats opgehaald via endpoint:', verblijfplaatsData);
                        }
                    })
                    .catch(function(error) {
                        console.warn('Fout bij ophalen verblijfplaats endpoint (kan timeout zijn):', error.message);
                    });
                }
            })
            .catch(function(error) {
                console.error('Fout bij ophalen persoon:', error);
            });
        }
        
        // Haal relaties op en vul deze in
        if (bsn) {
            loadAndFillRelations(bsn);
            // Haal ook aanvullende data op voor andere categorieën
            loadAdditionalCategoryData(bsn, person);
            // Haal verblijfplaats historie op
            loadVerblijfplaatsHistorie(bsn);
        }
        
        console.log('Person display updated met persoon data:', person);
    }
    
    /**
     * Switch tussen Actueel en Historie view voor verblijfplaats
     */
    function switchVerblijfplaatsView(viewType) {
        console.log('switchVerblijfplaatsView called with:', viewType);
        var actueelSection = document.getElementById('verblijfplaats-actueel');
        var historieTitle = document.getElementById('verblijfplaats-historie-title');
        var historieList = document.getElementById('verblijfplaats-historie-list');
        
        console.log('Elements found:', {
            actueelSection: !!actueelSection,
            historieTitle: !!historieTitle,
            historieList: !!historieList
        });
        
        if (viewType === 'actueel') {
            if (actueelSection) {
                actueelSection.style.display = 'grid';
                console.log('Actueel section shown');
            }
            if (historieTitle) historieTitle.style.display = 'none';
            if (historieList) historieList.style.display = 'none';
        } else if (viewType === 'historie') {
            if (actueelSection) {
                actueelSection.style.display = 'none';
                console.log('Actueel section hidden');
            }
            if (historieTitle) {
                historieTitle.style.display = 'block';
                console.log('Historie title shown');
            }
            if (historieList) {
                historieList.style.display = 'block';
                console.log('Historie list shown, content length:', historieList.innerHTML ? historieList.innerHTML.length : 0);
            }
        }
        
        // Update details title
        var detailsTitle = document.getElementById('details-title');
        if (detailsTitle) {
            if (viewType === 'actueel') {
                detailsTitle.textContent = 'Details: 07. Verblijfplaats (adres) - Actueel';
            } else if (viewType === 'historie') {
                detailsTitle.textContent = 'Details: 07. Verblijfplaats (adres) - Historie';
            }
        }
    }
    
    /**
     * Haal verblijfplaats historie op
     */
    function loadVerblijfplaatsHistorie(bsn) {
        if (!bsn) return;
        
        var baseUrl = API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn) + '/verblijfplaatshistorie';
        
        fetch(baseUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
            credentials: 'include'
        })
        .then(function(response) {
            if (!response.ok) {
                console.warn('Verblijfplaats historie endpoint status:', response.status);
                return { _embedded: { verblijfplaatshistorie: [] } };
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Verblijfplaats historie data:', data);
            var historie = data._embedded && data._embedded.verblijfplaatshistorie ? data._embedded.verblijfplaatshistorie : [];
            displayVerblijfplaatsHistorie(historie);
            updateVerblijfplaatsHistorieBadge(historie.length);
        })
        .catch(function(error) {
            console.warn('Fout bij ophalen verblijfplaats historie:', error);
            updateVerblijfplaatsHistorieBadge(0);
        });
    }
    
    /**
     * Toon verblijfplaats historie
     */
    function displayVerblijfplaatsHistorie(historie) {
        var container = document.getElementById('verblijfplaats-historie-list');
        console.log('displayVerblijfplaatsHistorie called');
        console.log('Container:', container);
        console.log('Historie data:', historie);
        console.log('Historie length:', historie ? historie.length : 0);
        
        if (!container) {
            console.error('Verblijfplaats historie container niet gevonden!');
            return;
        }
        
        if (!historie || historie.length === 0) {
            console.log('Geen historie data, showing empty message');
            container.innerHTML = '<div class="no-historie">Geen historische adressen gevonden.</div>';
            return;
        }
        
        console.log('Building historie HTML for', historie.length, 'addresses');
        var html = '';
        historie.forEach(function(adres, index) {
            console.log('Processing historie item', index, adres);
            html += '<div class="historie-item">';
            html += '<div class="historie-item-header">';
            html += '<h5>Adres ' + (index + 1) + '</h5>';
            if (adres.datumIngangGeldigheid && adres.datumIngangGeldigheid.datum) {
                html += '<span class="historie-datum">Van: ' + formatDate(adres.datumIngangGeldigheid.datum) + '</span>';
            }
            if (adres.datumEindeGeldigheid && adres.datumEindeGeldigheid.datum) {
                html += '<span class="historie-datum">Tot: ' + formatDate(adres.datumEindeGeldigheid.datum) + '</span>';
            }
            html += '</div>';
            html += '<div class="detail-grid">';
            
            if (adres.straatnaam) {
                html += '<div class="detail-row"><div class="detail-label">07.10 Straatnaam</div><div class="detail-value">' + escapeHtml(adres.straatnaam) + '</div></div>';
            }
            if (adres.huisnummer) {
                html += '<div class="detail-row"><div class="detail-label">07.20 Huisnummer</div><div class="detail-value">' + escapeHtml(adres.huisnummer) + '</div></div>';
            }
            if (adres.huisnummertoevoeging) {
                html += '<div class="detail-row"><div class="detail-label">07.30 Huisnummertoevoeging</div><div class="detail-value">' + escapeHtml(adres.huisnummertoevoeging) + '</div></div>';
            }
            if (adres.postcode) {
                html += '<div class="detail-row"><div class="detail-label">07.40 Postcode</div><div class="detail-value">' + escapeHtml(adres.postcode) + '</div></div>';
            }
            if (adres.woonplaatsnaam) {
                html += '<div class="detail-row"><div class="detail-label">07.50 Woonplaats</div><div class="detail-value">' + escapeHtml(adres.woonplaatsnaam) + '</div></div>';
            }
            if (adres.datumAanvangAdres && adres.datumAanvangAdres.datum) {
                html += '<div class="detail-row"><div class="detail-label">07.60 Datum Aanvang Adres</div><div class="detail-value">' + formatDate(adres.datumAanvangAdres.datum) + '</div></div>';
            }
            if (adres.datumIngangGeldigheid && adres.datumIngangGeldigheid.datum) {
                html += '<div class="detail-row"><div class="detail-label">07.70 Datum Ingang Geldigheid</div><div class="detail-value">' + formatDate(adres.datumIngangGeldigheid.datum) + '</div></div>';
            }
            if (adres.datumEindeGeldigheid && adres.datumEindeGeldigheid.datum) {
                html += '<div class="detail-row"><div class="detail-label">07.80 Datum Einde Geldigheid</div><div class="detail-value">' + formatDate(adres.datumEindeGeldigheid.datum) + '</div></div>';
            }
            
            html += '</div>';
            html += '</div>';
        });
        
        container.innerHTML = html;
    }
    
    /**
     * Update verblijfplaats historie badge
     */
    function updateVerblijfplaatsHistorieBadge(count) {
        var badge = document.getElementById('verblijfplaats-historie-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Haal aanvullende categorie data op die niet via relatie endpoints beschikbaar is
     */
    function loadAdditionalCategoryData(bsn, person) {
        var baseUrl = API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn);
        
        // Haal volledige persoon data op voor aanvullende velden
        fetch(baseUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
            credentials: 'include'
        })
        .then(function(response) {
            if (!response.ok) {
                console.warn('Kon aanvullende persoon data niet ophalen:', response.status);
                return {};
            }
            return response.json();
        })
        .then(function(fullPersonData) {
            console.log('Volledige persoon data opgehaald:', fullPersonData);
            
            // Update Persoon (02) - aanvullende velden
            // 62.10 datum ingang familierechtelijke betrekking
            var detailPersoonDatumIngangFamilierechtelijkeBetrekkingEl = document.getElementById('detail-persoon-datum-ingang-familierechtelijke-betrekking');
            if (detailPersoonDatumIngangFamilierechtelijkeBetrekkingEl) {
                var datumIngangFamilierechtelijkeBetrekking = fullPersonData.datumIngangFamilierechtelijkeBetrekking?.datum || 
                                                              fullPersonData.datumIngangFamilierechtelijkeBetrekking || 
                                                              fullPersonData.familierechtelijkeBetrekking?.datumIngang?.datum ||
                                                              fullPersonData.familierechtelijkeBetrekking?.datumIngang ||
                                                              '-';
                if (datumIngangFamilierechtelijkeBetrekking !== '-' && datumIngangFamilierechtelijkeBetrekking) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumIngangFamilierechtelijkeBetrekking === 'string' && datumIngangFamilierechtelijkeBetrekking.includes('-')) {
                        var parts = datumIngangFamilierechtelijkeBetrekking.split('-');
                        if (parts.length === 3) {
                            datumIngangFamilierechtelijkeBetrekking = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailPersoonDatumIngangFamilierechtelijkeBetrekkingEl.textContent = datumIngangFamilierechtelijkeBetrekking;
            }
            
            // 82.10 gemeente document
            var detailPersoonGemeenteDocumentEl = document.getElementById('detail-persoon-gemeente-document');
            if (detailPersoonGemeenteDocumentEl) {
                var gemeenteDocument = fullPersonData.gemeenteDocument || 
                                      fullPersonData.document?.gemeente || 
                                      fullPersonData.persoon?.gemeenteDocument ||
                                      '-';
                detailPersoonGemeenteDocumentEl.textContent = gemeenteDocument;
            }
            
            // 82.20 datum document
            var detailPersoonDatumDocumentEl = document.getElementById('detail-persoon-datum-document');
            if (detailPersoonDatumDocumentEl) {
                var datumDocument = fullPersonData.datumDocument?.datum || 
                                   fullPersonData.datumDocument || 
                                   fullPersonData.document?.datum?.datum ||
                                   fullPersonData.document?.datum ||
                                   '-';
                if (datumDocument !== '-' && datumDocument) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumDocument === 'string' && datumDocument.includes('-')) {
                        var parts = datumDocument.split('-');
                        if (parts.length === 3) {
                            datumDocument = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailPersoonDatumDocumentEl.textContent = datumDocument;
            }
            
            // 82.30 beschrijving document
            var detailPersoonBeschrijvingDocumentEl = document.getElementById('detail-persoon-beschrijving-document');
            if (detailPersoonBeschrijvingDocumentEl) {
                var beschrijvingDocument = fullPersonData.beschrijvingDocument || 
                                          fullPersonData.document?.beschrijving || 
                                          fullPersonData.persoon?.beschrijvingDocument ||
                                          '-';
                detailPersoonBeschrijvingDocumentEl.textContent = beschrijvingDocument;
            }
            
            // 85.10 ingangsdatum geldigheid
            var detailPersoonIngangsdatumGeldigheidEl = document.getElementById('detail-persoon-ingangsdatum-geldigheid');
            if (detailPersoonIngangsdatumGeldigheidEl) {
                var ingangsdatumGeldigheid = fullPersonData.ingangsdatumGeldigheid?.datum || 
                                            fullPersonData.ingangsdatumGeldigheid || 
                                            fullPersonData.persoon?.ingangsdatumGeldigheid?.datum ||
                                            fullPersonData.persoon?.ingangsdatumGeldigheid ||
                                            '-';
                if (ingangsdatumGeldigheid !== '-' && ingangsdatumGeldigheid) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof ingangsdatumGeldigheid === 'string' && ingangsdatumGeldigheid.includes('-')) {
                        var parts = ingangsdatumGeldigheid.split('-');
                        if (parts.length === 3) {
                            ingangsdatumGeldigheid = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailPersoonIngangsdatumGeldigheidEl.textContent = ingangsdatumGeldigheid;
            }
            
            // 85.11 volgcode geldigheid
            var detailPersoonVolgcodeGeldigheidEl = document.getElementById('detail-persoon-volgcode-geldigheid');
            if (detailPersoonVolgcodeGeldigheidEl) {
                var volgcodeGeldigheid = fullPersonData.volgcodeGeldigheid || 
                                        fullPersonData.persoon?.volgcodeGeldigheid || 
                                        '-';
                detailPersoonVolgcodeGeldigheidEl.textContent = volgcodeGeldigheid;
            }
            
            // 86.10 datum van opneming
            var detailPersoonDatumOpnemingEl = document.getElementById('detail-persoon-datum-opneming');
            if (detailPersoonDatumOpnemingEl) {
                var datumOpneming = fullPersonData.datumOpneming?.datum || 
                                   fullPersonData.datumOpneming || 
                                   fullPersonData.persoon?.datumOpneming?.datum ||
                                   fullPersonData.persoon?.datumOpneming ||
                                   '-';
                if (datumOpneming !== '-' && datumOpneming) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumOpneming === 'string' && datumOpneming.includes('-')) {
                        var parts = datumOpneming.split('-');
                        if (parts.length === 3) {
                            datumOpneming = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailPersoonDatumOpnemingEl.textContent = datumOpneming;
            }
            
            // Update Inschrijving (01) - alle velden
            // 01.10 a-nummer
            var detailInschrijvingAnummerEl = document.getElementById('detail-inschrijving-anummer');
            if (detailInschrijvingAnummerEl) {
                detailInschrijvingAnummerEl.textContent = fullPersonData.aNummer || fullPersonData.administratienummer || person.aNummer || '-';
            }
            
            // 01.20 burgerservicenummer
            var detailInschrijvingBsnEl = document.getElementById('detail-inschrijving-bsn');
            if (detailInschrijvingBsnEl) {
                detailInschrijvingBsnEl.textContent = bsn || fullPersonData.burgerservicenummer || '-';
            }
            
            // 82.10 gemeente document
            var detailInschrijvingGemeenteDocumentEl = document.getElementById('detail-inschrijving-gemeente-document');
            if (detailInschrijvingGemeenteDocumentEl) {
                var gemeenteDocument = fullPersonData.inschrijving?.gemeenteDocument || 
                                       fullPersonData.inschrijving?.gemeente || 
                                       fullPersonData.gemeenteDocument || 
                                       '-';
                detailInschrijvingGemeenteDocumentEl.textContent = gemeenteDocument;
            }
            
            // 82.20 datum document
            var detailInschrijvingDatumDocumentEl = document.getElementById('detail-inschrijving-datum-document');
            if (detailInschrijvingDatumDocumentEl) {
                var datumDocument = fullPersonData.inschrijving?.datumDocument?.datum || 
                                   fullPersonData.inschrijving?.datumDocument || 
                                   fullPersonData.datumDocument?.datum ||
                                   fullPersonData.datumDocument ||
                                   '-';
                if (datumDocument !== '-' && datumDocument) {
                    // Format datum als nodig
                    if (typeof datumDocument === 'string' && datumDocument.includes('-')) {
                        var parts = datumDocument.split('-');
                        if (parts.length === 3) {
                            datumDocument = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailInschrijvingDatumDocumentEl.textContent = datumDocument;
            }
            
            // 82.30 beschrijving document
            var detailInschrijvingBeschrijvingDocumentEl = document.getElementById('detail-inschrijving-beschrijving-document');
            if (detailInschrijvingBeschrijvingDocumentEl) {
                var beschrijvingDocument = fullPersonData.inschrijving?.beschrijvingDocument || 
                                          fullPersonData.inschrijving?.beschrijving || 
                                          fullPersonData.beschrijvingDocument || 
                                          '-';
                detailInschrijvingBeschrijvingDocumentEl.textContent = beschrijvingDocument;
            }
            
            // 85.10 ingangsdatum geldigheid
            var detailInschrijvingIngangsdatumGeldigheidEl = document.getElementById('detail-inschrijving-ingangsdatum-geldigheid');
            if (detailInschrijvingIngangsdatumGeldigheidEl) {
                var ingangsdatumGeldigheid = fullPersonData.inschrijving?.ingangsdatumGeldigheid?.datum || 
                                            fullPersonData.inschrijving?.ingangsdatumGeldigheid || 
                                            fullPersonData.ingangsdatumGeldigheid?.datum ||
                                            fullPersonData.ingangsdatumGeldigheid ||
                                            '-';
                if (ingangsdatumGeldigheid !== '-' && ingangsdatumGeldigheid) {
                    // Format datum als nodig
                    if (typeof ingangsdatumGeldigheid === 'string' && ingangsdatumGeldigheid.includes('-')) {
                        var parts = ingangsdatumGeldigheid.split('-');
                        if (parts.length === 3) {
                            ingangsdatumGeldigheid = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailInschrijvingIngangsdatumGeldigheidEl.textContent = ingangsdatumGeldigheid;
            }
            
            // 85.11 volgcode geldigheid
            var detailInschrijvingVolgcodeGeldigheidEl = document.getElementById('detail-inschrijving-volgcode-geldigheid');
            if (detailInschrijvingVolgcodeGeldigheidEl) {
                var volgcodeGeldigheid = fullPersonData.inschrijving?.volgcodeGeldigheid || 
                                        fullPersonData.volgcodeGeldigheid || 
                                        '-';
                detailInschrijvingVolgcodeGeldigheidEl.textContent = volgcodeGeldigheid;
            }
            
            // 86.10 datum van opneming
            var detailInschrijvingDatumOpnemingEl = document.getElementById('detail-inschrijving-datum-opneming');
            if (detailInschrijvingDatumOpnemingEl) {
                var datumOpneming = fullPersonData.inschrijving?.datumOpneming?.datum || 
                                   fullPersonData.inschrijving?.datumOpneming || 
                                   fullPersonData.datumOpneming?.datum ||
                                   fullPersonData.datumOpneming ||
                                   '-';
                if (datumOpneming !== '-' && datumOpneming) {
                    // Format datum als nodig
                    if (typeof datumOpneming === 'string' && datumOpneming.includes('-')) {
                        var parts = datumOpneming.split('-');
                        if (parts.length === 3) {
                            datumOpneming = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailInschrijvingDatumOpnemingEl.textContent = datumOpneming;
            }
            
            // Update Overlijden (11) - mogelijk beschikbaar in persoon data
            if (fullPersonData.overlijden) {
                var detailOverlijdenDatumEl = document.getElementById('detail-overlijden-datum');
                if (detailOverlijdenDatumEl && fullPersonData.overlijden.datum) {
                    detailOverlijdenDatumEl.textContent = fullPersonData.overlijden.datum.datum || '-';
                }
                var detailOverlijdenPlaatsEl = document.getElementById('detail-overlijden-plaats');
                if (detailOverlijdenPlaatsEl && fullPersonData.overlijden.plaats) {
                    detailOverlijdenPlaatsEl.textContent = fullPersonData.overlijden.plaats || '-';
                }
            }
            
            // Update Verblijfstitel (08) - mogelijk beschikbaar in verblijfplaats data
            // Deze velden worden mogelijk niet direct beschikbaar via Haal Centraal API
            // maar kunnen worden opgehaald uit de verblijfplaats data of directe database queries
            
            // 09.10 gemeente van inschrijving
            var detailVerblijfstitelGemeenteInschrijvingEl = document.getElementById('detail-verblijfstitel-gemeente-inschrijving');
            if (detailVerblijfstitelGemeenteInschrijvingEl) {
                var gemeenteInschrijving = fullPersonData.verblijfplaats?.gemeenteInschrijving || 
                                          fullPersonData.verblijfplaats?.gemeente || 
                                          fullPersonData.verblijfstitel?.gemeenteInschrijving ||
                                          '-';
                detailVerblijfstitelGemeenteInschrijvingEl.textContent = gemeenteInschrijving;
            }
            
            // 09.11 code gemeente van inschrijving
            var detailVerblijfstitelCodeGemeenteInschrijvingEl = document.getElementById('detail-verblijfstitel-code-gemeente-inschrijving');
            if (detailVerblijfstitelCodeGemeenteInschrijvingEl) {
                var codeGemeenteInschrijving = fullPersonData.verblijfplaats?.codeGemeenteInschrijving || 
                                              fullPersonData.verblijfplaats?.gemeentecode || 
                                              fullPersonData.verblijfstitel?.codeGemeenteInschrijving ||
                                              '-';
                detailVerblijfstitelCodeGemeenteInschrijvingEl.textContent = codeGemeenteInschrijving;
            }
            
            // 09.20 datum inschrijving
            var detailVerblijfstitelDatumInschrijvingEl = document.getElementById('detail-verblijfstitel-datum-inschrijving');
            if (detailVerblijfstitelDatumInschrijvingEl) {
                var datumInschrijving = fullPersonData.verblijfplaats?.datumInschrijving?.datum || 
                                       fullPersonData.verblijfplaats?.datumInschrijving || 
                                       fullPersonData.verblijfstitel?.datumInschrijving?.datum ||
                                       fullPersonData.verblijfstitel?.datumInschrijving ||
                                       '-';
                if (datumInschrijving !== '-' && datumInschrijving) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumInschrijving === 'string' && datumInschrijving.includes('-')) {
                        var parts = datumInschrijving.split('-');
                        if (parts.length === 3) {
                            datumInschrijving = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfstitelDatumInschrijvingEl.textContent = datumInschrijving;
            }
            
            // 10.10 functie adres
            var detailVerblijfstitelFunctieAdresEl = document.getElementById('detail-verblijfstitel-functie-adres');
            if (detailVerblijfstitelFunctieAdresEl) {
                var functieAdres = fullPersonData.verblijfplaats?.functieAdres || 
                                  fullPersonData.verblijfplaats?.functie || 
                                  fullPersonData.verblijfstitel?.functieAdres ||
                                  'Woonadres';
                detailVerblijfstitelFunctieAdresEl.textContent = functieAdres;
            }
            
            // 10.20 gemeentedeel
            var detailVerblijfstitelGemeentedeelEl = document.getElementById('detail-verblijfstitel-gemeentedeel');
            if (detailVerblijfstitelGemeentedeelEl) {
                var gemeentedeel = fullPersonData.verblijfplaats?.gemeentedeel || 
                                  fullPersonData.verblijfplaats?.deelgemeente || 
                                  fullPersonData.verblijfstitel?.gemeentedeel ||
                                  '-';
                detailVerblijfstitelGemeentedeelEl.textContent = gemeentedeel;
            }
            
            // 10.30 datum aanvang adreshouding
            var detailVerblijfstitelDatumAanvangAdreshoudingEl = document.getElementById('detail-verblijfstitel-datum-aanvang-adreshouding');
            if (detailVerblijfstitelDatumAanvangAdreshoudingEl) {
                var datumAanvangAdreshouding = fullPersonData.verblijfplaats?.datumAanvangAdreshouding?.datum || 
                                              fullPersonData.verblijfplaats?.datumAanvangAdreshouding || 
                                              fullPersonData.verblijfplaats?.datumAanvangAdres?.datum ||
                                              fullPersonData.verblijfplaats?.datumAanvangAdres ||
                                              fullPersonData.verblijfstitel?.datumAanvangAdreshouding?.datum ||
                                              fullPersonData.verblijfstitel?.datumAanvangAdreshouding ||
                                              '-';
                if (datumAanvangAdreshouding !== '-' && datumAanvangAdreshouding) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumAanvangAdreshouding === 'string' && datumAanvangAdreshouding.includes('-')) {
                        var parts = datumAanvangAdreshouding.split('-');
                        if (parts.length === 3) {
                            datumAanvangAdreshouding = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfstitelDatumAanvangAdreshoudingEl.textContent = datumAanvangAdreshouding;
            }
            
            // 11.10 straatnaam
            var detailVerblijfstitelStraatnaamEl = document.getElementById('detail-verblijfstitel-straatnaam');
            if (detailVerblijfstitelStraatnaamEl) {
                var straatnaam = fullPersonData.verblijfplaats?.straatnaam || 
                                fullPersonData.verblijfstitel?.straatnaam ||
                                '-';
                detailVerblijfstitelStraatnaamEl.textContent = straatnaam;
            }
            
            // 11.11 straatnaam (officieel)
            var detailVerblijfstitelStraatnaamOfficieelEl = document.getElementById('detail-verblijfstitel-straatnaam-officieel');
            if (detailVerblijfstitelStraatnaamOfficieelEl) {
                var straatnaamOfficieel = fullPersonData.verblijfplaats?.straatnaamOfficieel || 
                                         fullPersonData.verblijfplaats?.straatnaam?.officieel || 
                                         fullPersonData.verblijfstitel?.straatnaamOfficieel ||
                                         fullPersonData.verblijfplaats?.straatnaam ||
                                         '-';
                detailVerblijfstitelStraatnaamOfficieelEl.textContent = straatnaamOfficieel;
            }
            
            // 11.12 straatnaam (NEN)
            var detailVerblijfstitelStraatnaamNenEl = document.getElementById('detail-verblijfstitel-straatnaam-nen');
            if (detailVerblijfstitelStraatnaamNenEl) {
                var straatnaamNen = fullPersonData.verblijfplaats?.straatnaamNen || 
                                   fullPersonData.verblijfplaats?.straatnaam?.nen || 
                                   fullPersonData.verblijfstitel?.straatnaamNen ||
                                   fullPersonData.verblijfplaats?.straatnaam ||
                                   '-';
                detailVerblijfstitelStraatnaamNenEl.textContent = straatnaamNen;
            }
            
            // 11.15 openbare ruimte
            var detailVerblijfstitelOpenbareRuimteEl = document.getElementById('detail-verblijfstitel-openbare-ruimte');
            if (detailVerblijfstitelOpenbareRuimteEl) {
                var openbareRuimte = fullPersonData.verblijfplaats?.openbareRuimte || 
                                    fullPersonData.verblijfplaats?.openbareRuimteNaam || 
                                    fullPersonData.verblijfstitel?.openbareRuimte ||
                                    fullPersonData.verblijfplaats?.straatnaam ||
                                    '-';
                detailVerblijfstitelOpenbareRuimteEl.textContent = openbareRuimte;
            }
            
            // 11.20 huisnummer
            var detailVerblijfstitelHuisnummerEl = document.getElementById('detail-verblijfstitel-huisnummer');
            if (detailVerblijfstitelHuisnummerEl) {
                var huisnummer = fullPersonData.verblijfplaats?.huisnummer || 
                                fullPersonData.verblijfstitel?.huisnummer ||
                                '-';
                detailVerblijfstitelHuisnummerEl.textContent = huisnummer;
            }
            
            // 11.60 postcode
            var detailVerblijfstitelPostcodeEl = document.getElementById('detail-verblijfstitel-postcode');
            if (detailVerblijfstitelPostcodeEl) {
                var postcode = fullPersonData.verblijfplaats?.postcode || 
                              fullPersonData.verblijfstitel?.postcode ||
                              '-';
                detailVerblijfstitelPostcodeEl.textContent = postcode;
            }
            
            // 11.70 woonplaatsnaam
            var detailVerblijfstitelWoonplaatsnaamEl = document.getElementById('detail-verblijfstitel-woonplaatsnaam');
            if (detailVerblijfstitelWoonplaatsnaamEl) {
                var woonplaatsnaam = fullPersonData.verblijfplaats?.woonplaatsnaam || 
                                    fullPersonData.verblijfplaats?.woonplaats || 
                                    fullPersonData.verblijfstitel?.woonplaatsnaam ||
                                    '-';
                detailVerblijfstitelWoonplaatsnaamEl.textContent = woonplaatsnaam;
            }
            
            // 11.80 identificatie verblijfplaats
            var detailVerblijfstitelIdentificatieVerblijfplaatsEl = document.getElementById('detail-verblijfstitel-identificatie-verblijfplaats');
            if (detailVerblijfstitelIdentificatieVerblijfplaatsEl) {
                var identificatieVerblijfplaats = fullPersonData.verblijfplaats?.identificatieVerblijfplaats || 
                                                 fullPersonData.verblijfplaats?.verblijfplaatsIdentificatie || 
                                                 fullPersonData.verblijfstitel?.identificatieVerblijfplaats ||
                                                 '-';
                detailVerblijfstitelIdentificatieVerblijfplaatsEl.textContent = identificatieVerblijfplaats;
            }
            
            // 11.90 identificatiecode nummeraanduiding
            var detailVerblijfstitelIdentificatiecodeNummeraanduidingEl = document.getElementById('detail-verblijfstitel-identificatiecode-nummeraanduiding');
            if (detailVerblijfstitelIdentificatiecodeNummeraanduidingEl) {
                var identificatiecodeNummeraanduiding = fullPersonData.verblijfplaats?.identificatiecodeNummeraanduiding || 
                                                      fullPersonData.verblijfplaats?.nummeraanduidingIdentificatie || 
                                                      fullPersonData.verblijfstitel?.identificatiecodeNummeraanduiding ||
                                                      '-';
                detailVerblijfstitelIdentificatiecodeNummeraanduidingEl.textContent = identificatiecodeNummeraanduiding;
            }
            
            // 14.10 land vanwaar ingeschreven
            var detailVerblijfstitelLandVanwaarIngeschrevenEl = document.getElementById('detail-verblijfstitel-land-vanwaar-ingeschreven');
            if (detailVerblijfstitelLandVanwaarIngeschrevenEl) {
                var landVanwaarIngeschreven = fullPersonData.verblijfplaats?.landVanwaarIngeschreven || 
                                             fullPersonData.verblijfplaats?.landVanwaar || 
                                             fullPersonData.verblijfstitel?.landVanwaarIngeschreven ||
                                             '-';
                detailVerblijfstitelLandVanwaarIngeschrevenEl.textContent = landVanwaarIngeschreven;
            }
            
            // 14.20 datum vestiging in Nederland
            var detailVerblijfstitelDatumVestigingNederlandEl = document.getElementById('detail-verblijfstitel-datum-vestiging-nederland');
            if (detailVerblijfstitelDatumVestigingNederlandEl) {
                var datumVestigingNederland = fullPersonData.verblijfplaats?.datumVestigingNederland?.datum || 
                                            fullPersonData.verblijfplaats?.datumVestigingNederland || 
                                            fullPersonData.verblijfstitel?.datumVestigingNederland?.datum ||
                                            fullPersonData.verblijfstitel?.datumVestigingNederland ||
                                            '-';
                if (datumVestigingNederland !== '-' && datumVestigingNederland) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumVestigingNederland === 'string' && datumVestigingNederland.includes('-')) {
                        var parts = datumVestigingNederland.split('-');
                        if (parts.length === 3) {
                            datumVestigingNederland = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfstitelDatumVestigingNederlandEl.textContent = datumVestigingNederland;
            }
            
            // 72.10 omschrijving van de aangifte adreshouding
            var detailVerblijfstitelOmschrijvingAangifteAdreshoudingEl = document.getElementById('detail-verblijfstitel-omschrijving-aangifte-adreshouding');
            if (detailVerblijfstitelOmschrijvingAangifteAdreshoudingEl) {
                var omschrijvingAangifteAdreshouding = fullPersonData.verblijfplaats?.omschrijvingAangifteAdreshouding || 
                                                      fullPersonData.verblijfplaats?.aangifteAdreshouding || 
                                                      fullPersonData.verblijfstitel?.omschrijvingAangifteAdreshouding ||
                                                      'ingeschrevene';
                detailVerblijfstitelOmschrijvingAangifteAdreshoudingEl.textContent = omschrijvingAangifteAdreshouding;
            }
            
            // 85.10 ingangsdatum geldigheid
            var detailVerblijfstitelIngangsdatumGeldigheidEl = document.getElementById('detail-verblijfstitel-ingangsdatum-geldigheid');
            if (detailVerblijfstitelIngangsdatumGeldigheidEl) {
                var ingangsdatumGeldigheid = fullPersonData.verblijfplaats?.ingangsdatumGeldigheid?.datum || 
                                            fullPersonData.verblijfplaats?.ingangsdatumGeldigheid || 
                                            fullPersonData.verblijfplaats?.datumIngangGeldigheid?.datum ||
                                            fullPersonData.verblijfplaats?.datumIngangGeldigheid ||
                                            fullPersonData.verblijfstitel?.ingangsdatumGeldigheid?.datum ||
                                            fullPersonData.verblijfstitel?.ingangsdatumGeldigheid ||
                                            '-';
                if (ingangsdatumGeldigheid !== '-' && ingangsdatumGeldigheid) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof ingangsdatumGeldigheid === 'string' && ingangsdatumGeldigheid.includes('-')) {
                        var parts = ingangsdatumGeldigheid.split('-');
                        if (parts.length === 3) {
                            ingangsdatumGeldigheid = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfstitelIngangsdatumGeldigheidEl.textContent = ingangsdatumGeldigheid;
            }
            
            // 85.11 volgcode geldigheid
            var detailVerblijfstitelVolgcodeGeldigheidEl = document.getElementById('detail-verblijfstitel-volgcode-geldigheid');
            if (detailVerblijfstitelVolgcodeGeldigheidEl) {
                var volgcodeGeldigheid = fullPersonData.verblijfplaats?.volgcodeGeldigheid || 
                                        fullPersonData.verblijfplaats?.volgcode || 
                                        fullPersonData.verblijfstitel?.volgcodeGeldigheid ||
                                        '0';
                detailVerblijfstitelVolgcodeGeldigheidEl.textContent = volgcodeGeldigheid;
            }
            
            // 86.10 datum van opneming
            var detailVerblijfstitelDatumOpnemingEl = document.getElementById('detail-verblijfstitel-datum-opneming');
            if (detailVerblijfstitelDatumOpnemingEl) {
                var datumOpneming = fullPersonData.verblijfplaats?.datumOpneming?.datum || 
                                   fullPersonData.verblijfplaats?.datumOpneming || 
                                   fullPersonData.verblijfstitel?.datumOpneming?.datum ||
                                   fullPersonData.verblijfstitel?.datumOpneming ||
                                   '-';
                if (datumOpneming !== '-' && datumOpneming) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumOpneming === 'string' && datumOpneming.includes('-')) {
                        var parts = datumOpneming.split('-');
                        if (parts.length === 3) {
                            datumOpneming = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfstitelDatumOpnemingEl.textContent = datumOpneming;
            }
            
            // Update Verblijf in het buitenland (09) - mogelijk beschikbaar in persoon data
            // 01.10 a-nummer
            var detailVerblijfBuitenlandAnummerEl = document.getElementById('detail-verblijf-buitenland-anummer');
            if (detailVerblijfBuitenlandAnummerEl) {
                detailVerblijfBuitenlandAnummerEl.textContent = fullPersonData.aNummer || fullPersonData.administratienummer || person.aNummer || '-';
            }
            
            // 01.20 burgerservicenummer
            var detailVerblijfBuitenlandBsnEl = document.getElementById('detail-verblijf-buitenland-bsn');
            if (detailVerblijfBuitenlandBsnEl) {
                detailVerblijfBuitenlandBsnEl.textContent = bsn || fullPersonData.burgerservicenummer || '-';
            }
            
            // 02.10 voornamen
            var naam = fullPersonData.naam || person.naam || {};
            var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
            var detailVerblijfBuitenlandVoornamenEl = document.getElementById('detail-verblijf-buitenland-voornamen');
            if (detailVerblijfBuitenlandVoornamenEl) {
                detailVerblijfBuitenlandVoornamenEl.textContent = voornamen || '-';
            }
            
            // 02.40 geslachtsnaam
            var detailVerblijfBuitenlandGeslachtsnaamEl = document.getElementById('detail-verblijf-buitenland-geslachtsnaam');
            if (detailVerblijfBuitenlandGeslachtsnaamEl) {
                detailVerblijfBuitenlandGeslachtsnaamEl.textContent = naam.geslachtsnaam || '-';
            }
            
            // 03.10 geboortedatum
            var geboorte = fullPersonData.geboorte || person.geboorte || {};
            var geboortedatumStr = '-';
            if (geboorte.datum && geboorte.datum.datum) {
                var geboorteDate = new Date(geboorte.datum.datum);
                var dag = String(geboorteDate.getDate()).padStart(2, '0');
                var maand = String(geboorteDate.getMonth() + 1).padStart(2, '0');
                geboortedatumStr = dag + '-' + maand + '-' + geboorteDate.getFullYear();
            }
            var detailVerblijfBuitenlandGeboortedatumEl = document.getElementById('detail-verblijf-buitenland-geboortedatum');
            if (detailVerblijfBuitenlandGeboortedatumEl) {
                detailVerblijfBuitenlandGeboortedatumEl.textContent = geboortedatumStr;
            }
            
            // 03.20 geboorteplaats
            var detailVerblijfBuitenlandGeboorteplaatsEl = document.getElementById('detail-verblijf-buitenland-geboorteplaats');
            if (detailVerblijfBuitenlandGeboorteplaatsEl) {
                detailVerblijfBuitenlandGeboorteplaatsEl.textContent = geboorte.plaats || '-';
            }
            
            // 03.30 geboorteland
            var detailVerblijfBuitenlandGeboortelandEl = document.getElementById('detail-verblijf-buitenland-geboorteland');
            if (detailVerblijfBuitenlandGeboortelandEl) {
                detailVerblijfBuitenlandGeboortelandEl.textContent = geboorte.land || '-';
            }
            
            // 82.10 gemeente document
            var detailVerblijfBuitenlandGemeenteDocumentEl = document.getElementById('detail-verblijf-buitenland-gemeente-document');
            if (detailVerblijfBuitenlandGemeenteDocumentEl) {
                var gemeenteDocument = fullPersonData.verblijfBuitenland?.gemeenteDocument || 
                                      fullPersonData.verblijfBuitenland?.gemeente || 
                                      fullPersonData.gemeenteDocument ||
                                      '-';
                detailVerblijfBuitenlandGemeenteDocumentEl.textContent = gemeenteDocument;
            }
            
            // 82.20 datum document
            var detailVerblijfBuitenlandDatumDocumentEl = document.getElementById('detail-verblijf-buitenland-datum-document');
            if (detailVerblijfBuitenlandDatumDocumentEl) {
                var datumDocument = fullPersonData.verblijfBuitenland?.datumDocument?.datum || 
                                   fullPersonData.verblijfBuitenland?.datumDocument || 
                                   fullPersonData.datumDocument?.datum ||
                                   fullPersonData.datumDocument ||
                                   '-';
                if (datumDocument !== '-' && datumDocument) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumDocument === 'string' && datumDocument.includes('-')) {
                        var parts = datumDocument.split('-');
                        if (parts.length === 3) {
                            datumDocument = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfBuitenlandDatumDocumentEl.textContent = datumDocument;
            }
            
            // 82.30 beschrijving document
            var detailVerblijfBuitenlandBeschrijvingDocumentEl = document.getElementById('detail-verblijf-buitenland-beschrijving-document');
            if (detailVerblijfBuitenlandBeschrijvingDocumentEl) {
                var beschrijvingDocument = fullPersonData.verblijfBuitenland?.beschrijvingDocument || 
                                          fullPersonData.verblijfBuitenland?.beschrijving || 
                                          fullPersonData.beschrijvingDocument ||
                                          '-';
                detailVerblijfBuitenlandBeschrijvingDocumentEl.textContent = beschrijvingDocument;
            }
            
            // 85.10 ingangsdatum geldigheid
            var detailVerblijfBuitenlandIngangsdatumGeldigheidEl = document.getElementById('detail-verblijf-buitenland-ingangsdatum-geldigheid');
            if (detailVerblijfBuitenlandIngangsdatumGeldigheidEl) {
                var ingangsdatumGeldigheid = fullPersonData.verblijfBuitenland?.ingangsdatumGeldigheid?.datum || 
                                            fullPersonData.verblijfBuitenland?.ingangsdatumGeldigheid || 
                                            fullPersonData.ingangsdatumGeldigheid?.datum ||
                                            fullPersonData.ingangsdatumGeldigheid ||
                                            '-';
                if (ingangsdatumGeldigheid !== '-' && ingangsdatumGeldigheid) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof ingangsdatumGeldigheid === 'string' && ingangsdatumGeldigheid.includes('-')) {
                        var parts = ingangsdatumGeldigheid.split('-');
                        if (parts.length === 3) {
                            ingangsdatumGeldigheid = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfBuitenlandIngangsdatumGeldigheidEl.textContent = ingangsdatumGeldigheid;
            }
            
            // 85.11 volgcode geldigheid
            var detailVerblijfBuitenlandVolgcodeGeldigheidEl = document.getElementById('detail-verblijf-buitenland-volgcode-geldigheid');
            if (detailVerblijfBuitenlandVolgcodeGeldigheidEl) {
                var volgcodeGeldigheid = fullPersonData.verblijfBuitenland?.volgcodeGeldigheid || 
                                        fullPersonData.volgcodeGeldigheid || 
                                        '0';
                detailVerblijfBuitenlandVolgcodeGeldigheidEl.textContent = volgcodeGeldigheid;
            }
            
            // 86.10 datum van opneming
            var detailVerblijfBuitenlandDatumOpnemingEl = document.getElementById('detail-verblijf-buitenland-datum-opneming');
            if (detailVerblijfBuitenlandDatumOpnemingEl) {
                var datumOpneming = fullPersonData.verblijfBuitenland?.datumOpneming?.datum || 
                                   fullPersonData.verblijfBuitenland?.datumOpneming || 
                                   fullPersonData.datumOpneming?.datum ||
                                   fullPersonData.datumOpneming ||
                                   '-';
                if (datumOpneming !== '-' && datumOpneming) {
                    // Format datum als nodig (DD-MM-YYYY)
                    if (typeof datumOpneming === 'string' && datumOpneming.includes('-')) {
                        var parts = datumOpneming.split('-');
                        if (parts.length === 3) {
                            datumOpneming = parts[2] + '-' + parts[1] + '-' + parts[0];
                        }
                    }
                }
                detailVerblijfBuitenlandDatumOpnemingEl.textContent = datumOpneming;
            }
            
            // Haal verblijfsaantekening data op (categorie 12)
            loadVerblijfsaantekening(bsn);
            
            // Andere categorieën zijn mogelijk niet beschikbaar in Haal Centraal BRP Bevragen API
            // Deze worden leeg gelaten of kunnen later worden toegevoegd als de data beschikbaar komt
        })
        .catch(function(error) {
            console.warn('Fout bij ophalen aanvullende categorie data:', error);
        });
    }
    
    /**
     * Haal verblijfsaantekening data op en vul deze in
     */
    function loadVerblijfsaantekening(bsn) {
        var baseUrl = API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn) + '/verblijfsaantekening';
        
        fetch(baseUrl, {
            method: 'GET',
            headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
            credentials: 'include'
        })
        .then(function(response) {
            if (!response.ok) {
                console.warn('Kon verblijfsaantekening data niet ophalen:', response.status);
                return { _embedded: { verblijfsaantekeningen: [] } };
            }
            return response.json();
        })
        .then(function(data) {
            console.log('Verblijfsaantekening response:', data);
            
            var verblijfsaantekeningen = [];
            if (data && data._embedded && data._embedded.verblijfsaantekeningen) {
                verblijfsaantekeningen = data._embedded.verblijfsaantekeningen;
            } else if (Array.isArray(data)) {
                verblijfsaantekeningen = data;
            }
            
            // Vul de eerste aantekening in (of combineer alle aantekeningen)
            var detailVerblijfsaantekeningEl = document.getElementById('detail-verblijfsaantekening');
            if (detailVerblijfsaantekeningEl) {
                if (verblijfsaantekeningen.length > 0) {
                    // Combineer alle aantekeningen met komma's
                    var aantekeningen = verblijfsaantekeningen.map(function(aant) {
                        return aant.aantekening || '-';
                    }).filter(function(aant) {
                        return aant !== '-';
                    });
                    
                    if (aantekeningen.length > 0) {
                        detailVerblijfsaantekeningEl.textContent = aantekeningen.join(', ');
                    } else {
                        detailVerblijfsaantekeningEl.textContent = '-';
                    }
                } else {
                    detailVerblijfsaantekeningEl.textContent = '-';
                }
            }
        })
        .catch(function(error) {
            console.warn('Fout bij ophalen verblijfsaantekening data:', error);
            var detailVerblijfsaantekeningEl = document.getElementById('detail-verblijfsaantekening');
            if (detailVerblijfsaantekeningEl) {
                detailVerblijfsaantekeningEl.textContent = '-';
            }
        });
    }
    
    /**
     * Haal relaties op en vul deze in het formulier
     */
    function loadAndFillRelations(bsn) {
        var baseUrl = API_BASE + '/ingeschrevenpersonen/' + encodeURIComponent(bsn);
        
        // Haal alle relaties parallel op
        Promise.all([
            // Partners
            fetch(baseUrl + '/partners', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(function(r) {
                if (!r.ok) {
                    console.warn('Partners endpoint status:', r.status, r.statusText);
                    return { _embedded: { partners: [] } };
                }
                return r.json().then(function(data) {
                    console.log('Partners response:', data);
                    // Check of data een _embedded structuur heeft, anders wrap het
                    if (data && data._embedded) {
                        return data;
                    }
                    if (data && Array.isArray(data)) {
                        return { _embedded: { partners: data } };
                    }
                    if (data && data.partners) {
                        return { _embedded: { partners: data.partners } };
                    }
                    return { _embedded: { partners: [] } };
                }).catch(function(err) {
                    console.warn('Fout bij parsen partners response:', err);
                    return { _embedded: { partners: [] } };
                });
            }).catch(function(err) {
                console.warn('Fout bij ophalen partners:', err);
                return { _embedded: { partners: [] } };
            }),
            
            // Ouders
            fetch(baseUrl + '/ouders', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(function(r) {
                if (!r.ok) {
                    console.warn('Ouders endpoint status:', r.status, r.statusText);
                    return r.text().then(function(text) {
                        console.warn('Ouders response text:', text);
                        return { _embedded: { ouders: [] } };
                    });
                }
                return r.text().then(function(text) {
                    console.log('Ouders response text:', text);
                    try {
                        var data = JSON.parse(text);
                        console.log('Ouders parsed data:', data);
                        // Check of data een _embedded structuur heeft, anders wrap het
                        if (data && data._embedded) {
                            return data;
                        }
                        if (data && Array.isArray(data)) {
                            return { _embedded: { ouders: data } };
                        }
                        if (data && data.ouders) {
                            return { _embedded: { ouders: data.ouders } };
                        }
                        return { _embedded: { ouders: [] } };
                    } catch (err) {
                        console.warn('Fout bij parsen ouders JSON:', err, 'Text:', text);
                        return { _embedded: { ouders: [] } };
                    }
                });
            }).catch(function(err) {
                console.warn('Fout bij ophalen ouders:', err);
                return { _embedded: { ouders: [] } };
            }),
            
            // Kinderen
            fetch(baseUrl + '/kinderen', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(function(r) {
                if (!r.ok) {
                    console.warn('Kinderen endpoint status:', r.status, r.statusText);
                    return r.text().then(function(text) {
                        console.warn('Kinderen response text:', text);
                        return { _embedded: { kinderen: [] } };
                    });
                }
                return r.text().then(function(text) {
                    console.log('Kinderen response text:', text);
                    try {
                        var data = JSON.parse(text);
                        console.log('Kinderen parsed data:', data);
                        // Check of data een _embedded structuur heeft, anders wrap het
                        if (data && data._embedded) {
                            return data;
                        }
                        if (data && Array.isArray(data)) {
                            return { _embedded: { kinderen: data } };
                        }
                        if (data && data.kinderen) {
                            return { _embedded: { kinderen: data.kinderen } };
                        }
                        return { _embedded: { kinderen: [] } };
                    } catch (err) {
                        console.warn('Fout bij parsen kinderen JSON:', err, 'Text:', text);
                        return { _embedded: { kinderen: [] } };
                    }
                });
            }).catch(function(err) {
                console.warn('Fout bij ophalen kinderen:', err);
                return { _embedded: { kinderen: [] } };
            }),
            
            // Nationaliteiten
            fetch(baseUrl + '/nationaliteiten', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'OCS-APIRequest': 'true' },
                credentials: 'include'
            }).then(function(r) {
                return r.ok ? r.json() : { _embedded: { nationaliteiten: [] } };
            }).catch(function(err) {
                console.warn('Fout bij ophalen nationaliteiten:', err);
                return { _embedded: { nationaliteiten: [] } };
            })
        ]).then(function(results) {
            console.log('Alle relatie responses ontvangen:', results);
            
            var partnersData = results[0];
            var oudersData = results[1];
            var kinderenData = results[2];
            var nationaliteitenData = results[3];
            
            // Vul partner in
            var partners = partnersData._embedded && partnersData._embedded.partners ? partnersData._embedded.partners : [];
            console.log('Partners gevonden:', partners.length, partners);
            if (partners.length > 0) {
                fillPartner(partners[0]);
            }
            
            // Vul ouders in
            var ouders = oudersData._embedded && oudersData._embedded.ouders ? oudersData._embedded.ouders : [];
            console.log('Ouders gevonden:', ouders.length, ouders);
            if (ouders.length > 0) {
                fillOuder(ouders[0], 1);
            }
            if (ouders.length > 1) {
                fillOuder(ouders[1], 2);
            }
            
            // Vul kinderen in
            var kinderen = kinderenData._embedded && kinderenData._embedded.kinderen ? kinderenData._embedded.kinderen : [];
            console.log('Kinderen gevonden:', kinderen.length, kinderen);
            clearKinderen();
            kinderen.forEach(function(kind) {
                console.log('Kind toevoegen:', kind);
                addKind(kind);
            });
            
            // Vul nationaliteiten in
            var nationaliteiten = nationaliteitenData._embedded && nationaliteitenData._embedded.nationaliteiten ? nationaliteitenData._embedded.nationaliteiten : [];
            console.log('Nationaliteiten gevonden:', nationaliteiten.length, nationaliteiten);
            clearNationaliteiten();
            nationaliteiten.forEach(function(nationaliteit) {
                addNationaliteit(nationaliteit);
            });
        }).catch(function(error) {
            console.error('Fout bij ophalen relaties:', error);
        });
    }
    
    /**
     * Vul partner velden in
     */
    function fillPartner(partner) {
        var naam = partner.naam || {};
        var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
        var geboorte = partner.geboorte || {};
        var huwelijk = partner.huwelijk || {};
        
        // Format datum helper functie
        function formatDatum(datum) {
            if (!datum || datum === '-') return '-';
            if (typeof datum === 'string' && datum.includes('-')) {
                var parts = datum.split('-');
                if (parts.length === 3) {
                    return parts[2] + '-' + parts[1] + '-' + parts[0];
                }
            }
            return datum;
        }
        
        // Format geboortedatum
        var geboortedatumStr = '-';
        if (geboorte.datum && geboorte.datum.datum) {
            var geboorteDate = new Date(geboorte.datum.datum);
            var dag = String(geboorteDate.getDate()).padStart(2, '0');
            var maand = String(geboorteDate.getMonth() + 1).padStart(2, '0');
            geboortedatumStr = dag + '-' + maand + '-' + geboorteDate.getFullYear();
        }
        
        // Format geslachtsaanduiding
        var geslachtStr = partner.geslachtsaanduiding || '-';
        if (geslachtStr === 'man') geslachtStr = 'Man';
        if (geslachtStr === 'vrouw') geslachtStr = 'Vrouw';
        if (geslachtStr === 'onbekend') geslachtStr = 'Onbekend';
        
        // 01.10 a-nummer
        var detailPartnerAnummerEl = document.getElementById('detail-partner-anummer');
        if (detailPartnerAnummerEl) detailPartnerAnummerEl.textContent = partner.aNummer || partner.administratienummer || '-';
        
        // 01.20 burgerservicenummer
        var detailPartnerBsnEl = document.getElementById('detail-partner-bsn');
        if (detailPartnerBsnEl) detailPartnerBsnEl.textContent = partner.burgerservicenummer || '-';
        
        // 02.10 voornamen
        var detailPartnerVoornamenEl = document.getElementById('detail-partner-voornamen');
        if (detailPartnerVoornamenEl) detailPartnerVoornamenEl.textContent = voornamen || '-';
        
        // 02.40 geslachtsnaam
        var detailPartnerGeslachtsnaamEl = document.getElementById('detail-partner-geslachtsnaam');
        if (detailPartnerGeslachtsnaamEl) detailPartnerGeslachtsnaamEl.textContent = naam.geslachtsnaam || '-';
        
        // 03.10 geboortedatum
        var detailPartnerGeboortedatumEl = document.getElementById('detail-partner-geboortedatum');
        if (detailPartnerGeboortedatumEl) detailPartnerGeboortedatumEl.textContent = geboortedatumStr;
        
        // 03.20 geboorteplaats
        var detailPartnerGeboorteplaatsEl = document.getElementById('detail-partner-geboorteplaats');
        if (detailPartnerGeboorteplaatsEl) detailPartnerGeboorteplaatsEl.textContent = geboorte.plaats || '-';
        
        // 03.30 geboorteland
        var detailPartnerGeboortelandEl = document.getElementById('detail-partner-geboorteland');
        if (detailPartnerGeboortelandEl) {
            var geboorteland = geboorte.land?.omschrijving || geboorte.land || '-';
            detailPartnerGeboortelandEl.textContent = geboorteland;
        }
        
        // 04.10 geslachtsaanduiding
        var detailPartnerGeslachtsaanduidingEl = document.getElementById('detail-partner-geslachtsaanduiding');
        if (detailPartnerGeslachtsaanduidingEl) detailPartnerGeslachtsaanduidingEl.textContent = geslachtStr;
        
        // 06.10 datum huwelijkssluiting/aangaan GPS
        var detailPartnerDatumHuwelijkssluitingEl = document.getElementById('detail-partner-datum-huwelijkssluiting');
        if (detailPartnerDatumHuwelijkssluitingEl) {
            var datumHuwelijkssluiting = huwelijk.datumSluiting?.datum || huwelijk.datumSluiting || partner.datumSluiting?.datum || partner.datumSluiting || '-';
            detailPartnerDatumHuwelijkssluitingEl.textContent = formatDatum(datumHuwelijkssluiting);
        }
        
        // 06.20 plaats huwelijkssluiting/aangaan GPS
        var detailPartnerPlaatsHuwelijkssluitingEl = document.getElementById('detail-partner-plaats-huwelijkssluiting');
        if (detailPartnerPlaatsHuwelijkssluitingEl) {
            detailPartnerPlaatsHuwelijkssluitingEl.textContent = huwelijk.plaatsSluiting || partner.plaatsSluiting || '-';
        }
        
        // 06.30 land huwelijkssluiting/aangaan GPS
        var detailPartnerLandHuwelijkssluitingEl = document.getElementById('detail-partner-land-huwelijkssluiting');
        if (detailPartnerLandHuwelijkssluitingEl) {
            var landHuwelijkssluiting = huwelijk.landSluiting?.omschrijving || huwelijk.landSluiting || partner.landSluiting?.omschrijving || partner.landSluiting || '-';
            detailPartnerLandHuwelijkssluitingEl.textContent = landHuwelijkssluiting;
        }
        
        // 15.10 soort verbintenis
        var detailPartnerSoortVerbintenisEl = document.getElementById('detail-partner-soort-verbintenis');
        if (detailPartnerSoortVerbintenisEl) {
            var soortVerbintenis = partner.soortVerbintenis || huwelijk.soortVerbintenis || partner.soort || '-';
            if (soortVerbintenis === 'huwelijk') soortVerbintenis = 'Huwelijk';
            if (soortVerbintenis === 'geregistreerd_partnerschap') soortVerbintenis = 'Geregistreerd partnerschap';
            detailPartnerSoortVerbintenisEl.textContent = soortVerbintenis;
        }
        
        // 82.10 gemeente document
        var detailPartnerGemeenteDocumentEl = document.getElementById('detail-partner-gemeente-document');
        if (detailPartnerGemeenteDocumentEl) {
            detailPartnerGemeenteDocumentEl.textContent = partner.gemeenteDocument || partner.document?.gemeente || huwelijk.gemeenteDocument || '-';
        }
        
        // 82.20 datum document
        var detailPartnerDatumDocumentEl = document.getElementById('detail-partner-datum-document');
        if (detailPartnerDatumDocumentEl) {
            var datumDocument = partner.datumDocument?.datum || partner.datumDocument || huwelijk.datumDocument?.datum || huwelijk.datumDocument || '-';
            detailPartnerDatumDocumentEl.textContent = formatDatum(datumDocument);
        }
        
        // 82.30 beschrijving document
        var detailPartnerBeschrijvingDocumentEl = document.getElementById('detail-partner-beschrijving-document');
        if (detailPartnerBeschrijvingDocumentEl) {
            detailPartnerBeschrijvingDocumentEl.textContent = partner.beschrijvingDocument || partner.document?.beschrijving || huwelijk.beschrijvingDocument || '-';
        }
        
        // 85.10 ingangsdatum geldigheid
        var detailPartnerIngangsdatumGeldigheidEl = document.getElementById('detail-partner-ingangsdatum-geldigheid');
        if (detailPartnerIngangsdatumGeldigheidEl) {
            var ingangsdatumGeldigheid = partner.ingangsdatumGeldigheid?.datum || partner.ingangsdatumGeldigheid || partner.datumIngangGeldigheid?.datum || partner.datumIngangGeldigheid || huwelijk.ingangsdatumGeldigheid?.datum || huwelijk.ingangsdatumGeldigheid || '-';
            detailPartnerIngangsdatumGeldigheidEl.textContent = formatDatum(ingangsdatumGeldigheid);
        }
        
        // 85.11 volgcode geldigheid
        var detailPartnerVolgcodeGeldigheidEl = document.getElementById('detail-partner-volgcode-geldigheid');
        if (detailPartnerVolgcodeGeldigheidEl) {
            detailPartnerVolgcodeGeldigheidEl.textContent = partner.volgcodeGeldigheid || partner.volgcode || huwelijk.volgcodeGeldigheid || huwelijk.volgcode || '0';
        }
        
        // 86.10 datum van opneming
        var detailPartnerDatumOpnemingEl = document.getElementById('detail-partner-datum-opneming');
        if (detailPartnerDatumOpnemingEl) {
            var datumOpneming = partner.datumOpneming?.datum || partner.datumOpneming || huwelijk.datumOpneming?.datum || huwelijk.datumOpneming || '-';
            detailPartnerDatumOpnemingEl.textContent = formatDatum(datumOpneming);
        }
        
        // Update burgerlijke staat in overview
        var personStatusEl = document.getElementById('display-person-status');
        if (personStatusEl && partner.burgerservicenummer) {
            var soortVerbintenis = partner.soortVerbintenis || huwelijk.soortVerbintenis || partner.soort || '';
            if (soortVerbintenis === 'huwelijk') {
                personStatusEl.textContent = 'Gehuwd';
            } else if (soortVerbintenis === 'geregistreerd_partnerschap') {
                personStatusEl.textContent = 'Geregistreerd partnerschap';
            } else {
                personStatusEl.textContent = 'Partner';
            }
        }
    }
    
    /**
     * Vul ouder velden in
     */
    function fillOuder(ouder, nummer) {
        var naam = ouder.naam || {};
        var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
        
        // Update ouder details
        var detailOuderBsnEl = document.getElementById('detail-ouder' + nummer + '-bsn');
        if (detailOuderBsnEl) detailOuderBsnEl.textContent = ouder.burgerservicenummer || '-';
        
        var detailOuderVoornamenEl = document.getElementById('detail-ouder' + nummer + '-voornamen');
        if (detailOuderVoornamenEl) detailOuderVoornamenEl.textContent = voornamen || '-';
        
        var detailOuderVoorvoegselEl = document.getElementById('detail-ouder' + nummer + '-voorvoegsel');
        if (detailOuderVoorvoegselEl) detailOuderVoorvoegselEl.textContent = naam.voorvoegsel || '-';
        
        var detailOuderGeslachtsnaamEl = document.getElementById('detail-ouder' + nummer + '-geslachtsnaam');
        if (detailOuderGeslachtsnaamEl) detailOuderGeslachtsnaamEl.textContent = naam.geslachtsnaam || '-';
        
        // 62.10 datum ingang familierechtelijke betrekking
        var detailOuderDatumIngangFamilierechtelijkeBetrekkingEl = document.getElementById('detail-ouder' + nummer + '-datum-ingang-familierechtelijke-betrekking');
        if (detailOuderDatumIngangFamilierechtelijkeBetrekkingEl) {
            var datumIngangFamilierechtelijkeBetrekking = ouder.datumIngangFamilierechtelijkeBetrekking?.datum || 
                                                          ouder.datumIngangFamilierechtelijkeBetrekking || 
                                                          ouder.familierechtelijkeBetrekking?.datumIngang?.datum ||
                                                          ouder.familierechtelijkeBetrekking?.datumIngang ||
                                                          '-';
            if (datumIngangFamilierechtelijkeBetrekking !== '-' && datumIngangFamilierechtelijkeBetrekking) {
                // Format datum als nodig (DD-MM-YYYY)
                if (typeof datumIngangFamilierechtelijkeBetrekking === 'string' && datumIngangFamilierechtelijkeBetrekking.includes('-')) {
                    var parts = datumIngangFamilierechtelijkeBetrekking.split('-');
                    if (parts.length === 3) {
                        datumIngangFamilierechtelijkeBetrekking = parts[2] + '-' + parts[1] + '-' + parts[0];
                    }
                }
            }
            detailOuderDatumIngangFamilierechtelijkeBetrekkingEl.textContent = datumIngangFamilierechtelijkeBetrekking;
        }
        
        // 82.10 gemeente document
        var detailOuderGemeenteDocumentEl = document.getElementById('detail-ouder' + nummer + '-gemeente-document');
        if (detailOuderGemeenteDocumentEl) {
            var gemeenteDocument = ouder.gemeenteDocument || 
                                  ouder.document?.gemeente || 
                                  '-';
            detailOuderGemeenteDocumentEl.textContent = gemeenteDocument;
        }
        
        // 82.20 datum document
        var detailOuderDatumDocumentEl = document.getElementById('detail-ouder' + nummer + '-datum-document');
        if (detailOuderDatumDocumentEl) {
            var datumDocument = ouder.datumDocument?.datum || 
                               ouder.datumDocument || 
                               ouder.document?.datum?.datum ||
                               ouder.document?.datum ||
                               '-';
            if (datumDocument !== '-' && datumDocument) {
                // Format datum als nodig (DD-MM-YYYY)
                if (typeof datumDocument === 'string' && datumDocument.includes('-')) {
                    var parts = datumDocument.split('-');
                    if (parts.length === 3) {
                        datumDocument = parts[2] + '-' + parts[1] + '-' + parts[0];
                    }
                }
            }
            detailOuderDatumDocumentEl.textContent = datumDocument;
        }
        
        // 82.30 beschrijving document
        var detailOuderBeschrijvingDocumentEl = document.getElementById('detail-ouder' + nummer + '-beschrijving-document');
        if (detailOuderBeschrijvingDocumentEl) {
            var beschrijvingDocument = ouder.beschrijvingDocument || 
                                      ouder.document?.beschrijving || 
                                      '-';
            detailOuderBeschrijvingDocumentEl.textContent = beschrijvingDocument;
        }
        
        // 85.10 ingangsdatum geldigheid
        var detailOuderIngangsdatumGeldigheidEl = document.getElementById('detail-ouder' + nummer + '-ingangsdatum-geldigheid');
        if (detailOuderIngangsdatumGeldigheidEl) {
            var ingangsdatumGeldigheid = ouder.ingangsdatumGeldigheid?.datum || 
                                        ouder.ingangsdatumGeldigheid || 
                                        '-';
            if (ingangsdatumGeldigheid !== '-' && ingangsdatumGeldigheid) {
                // Format datum als nodig (DD-MM-YYYY)
                if (typeof ingangsdatumGeldigheid === 'string' && ingangsdatumGeldigheid.includes('-')) {
                    var parts = ingangsdatumGeldigheid.split('-');
                    if (parts.length === 3) {
                        ingangsdatumGeldigheid = parts[2] + '-' + parts[1] + '-' + parts[0];
                    }
                }
            }
            detailOuderIngangsdatumGeldigheidEl.textContent = ingangsdatumGeldigheid;
        }
        
        // 85.11 volgcode geldigheid
        var detailOuderVolgcodeGeldigheidEl = document.getElementById('detail-ouder' + nummer + '-volgcode-geldigheid');
        if (detailOuderVolgcodeGeldigheidEl) {
            var volgcodeGeldigheid = ouder.volgcodeGeldigheid || 
                                    '-';
            detailOuderVolgcodeGeldigheidEl.textContent = volgcodeGeldigheid;
        }
        
        // 86.10 datum van opneming
        var detailOuderDatumOpnemingEl = document.getElementById('detail-ouder' + nummer + '-datum-opneming');
        if (detailOuderDatumOpnemingEl) {
            var datumOpneming = ouder.datumOpneming?.datum || 
                               ouder.datumOpneming || 
                               '-';
            if (datumOpneming !== '-' && datumOpneming) {
                // Format datum als nodig (DD-MM-YYYY)
                if (typeof datumOpneming === 'string' && datumOpneming.includes('-')) {
                    var parts = datumOpneming.split('-');
                    if (parts.length === 3) {
                        datumOpneming = parts[2] + '-' + parts[1] + '-' + parts[0];
                    }
                }
            }
            detailOuderDatumOpnemingEl.textContent = datumOpneming;
        }
        
        // Extra velden voor Ouder 2 (categorie 04)
        if (nummer === 2) {
            // 05.10 nationaliteit
            var detailOuder2NationaliteitEl = document.getElementById('detail-ouder2-nationaliteit');
            if (detailOuder2NationaliteitEl) {
                var nationaliteit = ouder.nationaliteit?.omschrijving || 
                                   ouder.nationaliteit || 
                                   ouder.nationaliteiten?.[0]?.omschrijving ||
                                   '-';
                detailOuder2NationaliteitEl.textContent = nationaliteit;
            }
            
            // 63.10 reden opname nationaliteit
            var detailOuder2RedenOpnameNationaliteitEl = document.getElementById('detail-ouder2-reden-opname-nationaliteit');
            if (detailOuder2RedenOpnameNationaliteitEl) {
                var redenOpnameNationaliteit = ouder.redenOpnameNationaliteit || 
                                              ouder.nationaliteit?.redenOpname || 
                                              ouder.nationaliteiten?.[0]?.redenOpname ||
                                              '-';
                detailOuder2RedenOpnameNationaliteitEl.textContent = redenOpnameNationaliteit;
            }
        }
    }
    
    /**
     * Voeg kind toe aan display
     */
    function addKind(kind) {
        var container = document.getElementById('kinderen-details-list');
        if (!container) return;
        
        var naam = kind.naam || {};
        var voornamen = naam.voornamen ? (Array.isArray(naam.voornamen) ? naam.voornamen.join(' ') : naam.voornamen) : '';
        var voorvoegsel = naam.voorvoegsel || '';
        var geslachtsnaam = naam.geslachtsnaam || '';
        var volledigeNaam = voorvoegsel ? geslachtsnaam + ', ' + voornamen + ' ' + voorvoegsel : geslachtsnaam + ', ' + voornamen;
        var kindIndex = container.children.length;
        
        // Format datum helper functie
        function formatDatum(datum) {
            if (!datum || datum === '-') return '-';
            if (typeof datum === 'string' && datum.includes('-')) {
                var parts = datum.split('-');
                if (parts.length === 3) {
                    return parts[2] + '-' + parts[1] + '-' + parts[0];
                }
            }
            return datum;
        }
        
        var geboorte = kind.geboorte || {};
        var geboortedatumStr = '-';
        if (geboorte.datum && geboorte.datum.datum) {
            var geboorteDate = new Date(geboorte.datum.datum);
            var dag = String(geboorteDate.getDate()).padStart(2, '0');
            var maand = String(geboorteDate.getMonth() + 1).padStart(2, '0');
            geboortedatumStr = dag + '-' + maand + '-' + geboorteDate.getFullYear();
        }
        
        var kindHtml = '<div class="kind-detail-item" data-index="' + kindIndex + '">';
        kindHtml += '<div class="detail-section-title">Actueel</div>';
        kindHtml += '<div class="detail-grid">';
        
        // 01.10 a-nummer
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">01.10 a-nummer</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(kind.aNummer || kind.administratienummer || '-') + '</div>';
        kindHtml += '</div>';
        
        // 01.20 burgerservicenummer
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">01.20 burgerservicenummer</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(kind.burgerservicenummer || '-') + '</div>';
        kindHtml += '</div>';
        
        // 02.10 voornamen
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">02.10 voornamen</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(voornamen || '-') + '</div>';
        kindHtml += '</div>';
        
        // 02.40 geslachtsnaam
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">02.40 geslachtsnaam</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(geslachtsnaam || '-') + '</div>';
        kindHtml += '</div>';
        
        // 03.10 geboortedatum
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">03.10 geboortedatum</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(geboortedatumStr) + '</div>';
        kindHtml += '</div>';
        
        // 03.20 geboorteplaats
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">03.20 geboorteplaats</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(geboorte.plaats || '-') + '</div>';
        kindHtml += '</div>';
        
        // 03.30 geboorteland
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">03.30 geboorteland</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(geboorte.land || '-') + '</div>';
        kindHtml += '</div>';
        
        // 82.10 gemeente document
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">82.10 gemeente document</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(kind.gemeenteDocument || kind.document?.gemeente || '-') + '</div>';
        kindHtml += '</div>';
        
        // 82.20 datum document
        var datumDocument = kind.datumDocument?.datum || kind.datumDocument || '-';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">82.20 datum document</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(formatDatum(datumDocument)) + '</div>';
        kindHtml += '</div>';
        
        // 82.30 beschrijving document
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">82.30 beschrijving document</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(kind.beschrijvingDocument || kind.document?.beschrijving || '-') + '</div>';
        kindHtml += '</div>';
        
        // 85.10 ingangsdatum geldigheid
        var ingangsdatumGeldigheid = kind.ingangsdatumGeldigheid?.datum || 
                                    kind.ingangsdatumGeldigheid || 
                                    kind.datumIngangGeldigheid?.datum ||
                                    kind.datumIngangGeldigheid ||
                                    '-';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">85.10 ingangsdatum geldigheid</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(formatDatum(ingangsdatumGeldigheid)) + '</div>';
        kindHtml += '</div>';
        
        // 85.11 volgcode geldigheid
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">85.11 volgcode geldigheid</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(kind.volgcodeGeldigheid || kind.volgcode || '0') + '</div>';
        kindHtml += '</div>';
        
        // 86.10 datum van opneming
        var datumOpneming = kind.datumOpneming?.datum || 
                           kind.datumOpneming || 
                           '-';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">86.10 datum van opneming</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(formatDatum(datumOpneming)) + '</div>';
        kindHtml += '</div>';
        
        kindHtml += '</div>'; // Close detail-grid
        kindHtml += '</div>'; // Close kind-detail-item
        
        container.insertAdjacentHTML('beforeend', kindHtml);
        
        // Update badge
        updateKinderenBadge();
    }
    
    /**
     * Update kinderen badge
     */
    function updateKinderenBadge() {
        var container = document.getElementById('kinderen-details-list');
        var badge = document.getElementById('kinderen-badge');
        if (container && badge) {
            var count = container.children.length;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Wis alle kinderen
     */
    function clearKinderen() {
        var container = document.getElementById('kinderen-details-list');
        if (container) container.innerHTML = '';
        updateKinderenBadge();
    }
    
    /**
     * Voeg nationaliteit toe aan display
     */
    function addNationaliteit(nationaliteit) {
        var container = document.getElementById('nationaliteiten-details-list');
        if (!container) return;
        
        var natData = nationaliteit.nationaliteit || {};
        var natIndex = container.children.length;
        
        // Format datum helper functie
        function formatDatum(datum) {
            if (!datum || datum === '-') return '-';
            if (typeof datum === 'string' && datum.includes('-')) {
                var parts = datum.split('-');
                if (parts.length === 3) {
                    return parts[2] + '-' + parts[1] + '-' + parts[0];
                }
            }
            return datum;
        }
        
        var natHtml = '<div class="nationaliteit-detail-item" data-index="' + natIndex + '">';
        natHtml += '<div class="detail-section-title">Actueel</div>';
        natHtml += '<div class="detail-grid">';
        
        // 05.10 nationaliteit
        var nationaliteitStr = natData.naam || nationaliteit.naam || nationaliteit.nationaliteit || '-';
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">05.10 nationaliteit</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(nationaliteitStr) + '</div>';
        natHtml += '</div>';
        
        // 63.10 reden opname nationaliteit
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">63.10 reden opname nationaliteit</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(nationaliteit.redenOpname || nationaliteit.reden || '-') + '</div>';
        natHtml += '</div>';
        
        // 82.10 gemeente document
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">82.10 gemeente document</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(nationaliteit.gemeenteDocument || nationaliteit.document?.gemeente || '-') + '</div>';
        natHtml += '</div>';
        
        // 82.20 datum document
        var datumDocument = nationaliteit.datumDocument?.datum || nationaliteit.datumDocument || '-';
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">82.20 datum document</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(formatDatum(datumDocument)) + '</div>';
        natHtml += '</div>';
        
        // 82.30 beschrijving document
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">82.30 beschrijving document</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(nationaliteit.beschrijvingDocument || nationaliteit.document?.beschrijving || '-') + '</div>';
        natHtml += '</div>';
        
        // 85.10 ingangsdatum geldigheid
        var ingangsdatumGeldigheid = nationaliteit.ingangsdatumGeldigheid?.datum || nationaliteit.ingangsdatumGeldigheid || nationaliteit.datumIngang?.datum || nationaliteit.datumIngang || '-';
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">85.10 ingangsdatum geldigheid</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(formatDatum(ingangsdatumGeldigheid)) + '</div>';
        natHtml += '</div>';
        
        // 85.11 volgcode geldigheid
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">85.11 volgcode geldigheid</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(nationaliteit.volgcodeGeldigheid || nationaliteit.volgcode || '0') + '</div>';
        natHtml += '</div>';
        
        // 86.10 datum van opneming
        var datumOpneming = nationaliteit.datumOpneming?.datum || nationaliteit.datumOpneming || '-';
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">86.10 datum van opneming</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(formatDatum(datumOpneming)) + '</div>';
        natHtml += '</div>';
        
        natHtml += '</div>'; // Close detail-grid
        natHtml += '</div>'; // Close nationaliteit-detail-item
        
        container.insertAdjacentHTML('beforeend', natHtml);
        
        // Update badge
        updateNationaliteitBadge();
    }
    
    /**
     * Update nationaliteit badge
     */
    function updateNationaliteitBadge() {
        var container = document.getElementById('nationaliteiten-details-list');
        var badge = document.getElementById('nationaliteit-badge');
        if (container && badge) {
            var count = container.children.length;
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }
    
    /**
     * Wis alle nationaliteiten
     */
    function clearNationaliteiten() {
        var container = document.getElementById('nationaliteiten-details-list');
        if (container) container.innerHTML = '';
        updateNationaliteitBadge();
    }
    
    /**
     * Switch naar een andere category
     */
    function switchCategory(categoryName) {
        // Update active category
        var categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(function(item) {
            item.classList.remove('active');
            if (item.getAttribute('data-category') === categoryName) {
                item.classList.add('active');
            }
        });
        
        // Update active detail section
        var detailSections = document.querySelectorAll('.detail-section');
        detailSections.forEach(function(section) {
            section.classList.remove('active');
            section.style.display = 'none';
            if (section.getAttribute('data-category') === categoryName) {
                section.classList.add('active');
                section.style.display = 'block';
                
                // Als dit verblijfplaats is, reset naar actueel view
                if (categoryName === 'verblijfplaats') {
                    // Reset submenu naar actueel
                    var verblijfplaatsCategory = document.querySelector('.category-item[data-category="verblijfplaats"]');
                    if (verblijfplaatsCategory) {
                        var submenuItems = verblijfplaatsCategory.querySelectorAll('.submenu-item');
                        submenuItems.forEach(function(sub) {
                            sub.classList.remove('active');
                            if (sub.getAttribute('data-sub') === 'actueel') {
                                sub.classList.add('active');
                            }
                        });
                    }
                    // Toon actueel, verberg historie
                    switchVerblijfplaatsView('actueel');
                }
            }
        });
        
        // Update details title
        var categoryLabels = {
            'inschrijving': '01. Inschrijving',
            'persoon': '02. Persoon',
            'ouder1': '03. Ouder 1',
            'ouder2': '04. Ouder 2',
            'nationaliteit': '05. Nationaliteit',
            'partner': '06. Huwelijk/Geregistreerd partnerschap',
            'verblijfplaats': '07. Verblijfplaats (adres)',
            'verblijfstitel': '08. Verblijfstitel',
            'verblijf-buitenland': '09. Verblijf in het buitenland',
            'kinderen': '10. Kind',
            'overlijden': '11. Overlijden',
            'verblijfsaantekening': '12. Verblijfsaantekening EU/EER',
            'gezag': '13. Gezag',
            'reisdocument': '14. Reisdocument',
            'kiesrecht': '15. Kiesrecht',
            'verwijzing': '16. Verwijzing',
            'contactgegevens': '21. Contactgegevens (optioneel)'
        };
        var detailsTitle = document.getElementById('details-title');
        if (detailsTitle && categoryLabels[categoryName]) {
            detailsTitle.textContent = 'Details: ' + categoryLabels[categoryName];
        }
    }
    
    function init() {
        console.log('Prefill test init called, initialized:', initialized);
        if (initialized) return;
        
        var searchInput = document.getElementById('prefill-search-input');
        var searchBtn = document.getElementById('prefill-search-btn');
        
        console.log('Search elements:', {
            searchInput: !!searchInput,
            searchBtn: !!searchBtn
        });
        
        if (!searchInput || !searchBtn) {
            console.warn('Search elements not found');
            return;
        }
        
        initialized = true;
        console.log('Initialized, setting up event listeners');
        
        // Search button click
        searchBtn.addEventListener('click', function(e) {
            console.log('Search button clicked');
            e.preventDefault();
            var searchTerm = searchInput.value.trim();
            console.log('Search term:', searchTerm);
            searchPersons(searchTerm);
        });
        
        // Enter key in search input
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchBtn.click();
            }
        });
        
        // Category navigation
        var categoryItems = document.querySelectorAll('.category-item');
        categoryItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                // Don't switch if clicking on submenu
                if (e.target.closest('.category-submenu')) {
                    return;
                }
                var category = item.getAttribute('data-category');
                if (category) {
                    switchCategory(category);
                }
            });
        });
        
        // Submenu items - gebruik event delegation omdat items dynamisch kunnen zijn
        document.addEventListener('click', function(e) {
            var submenuItem = e.target.closest('.submenu-item');
            if (!submenuItem) return;
            
            e.preventDefault();
            e.stopPropagation();
            
            var subType = submenuItem.getAttribute('data-sub');
            var parent = submenuItem.closest('.category-item');
            var category = parent ? parent.getAttribute('data-category') : null;
            
            console.log('Submenu item clicked:', subType, 'Category:', category);
            
            // Zorg dat de parent category actief is
            if (parent && category) {
                switchCategory(category);
            }
            
            // Update active submenu item
            if (parent) {
                var submenuItems = parent.querySelectorAll('.submenu-item');
                submenuItems.forEach(function(sub) {
                    sub.classList.remove('active');
                });
                submenuItem.classList.add('active');
            }
            
            // Switch tussen Actueel/Historie view
            if (category === 'verblijfplaats') {
                console.log('Switching verblijfplaats view to:', subType);
                setTimeout(function() {
                    switchVerblijfplaatsView(subType);
                }, 100);
            } else if (category === 'persoon') {
                // TODO: Implementeer persoon historie als die beschikbaar is
                console.log('Persoon historie nog niet geïmplementeerd');
            }
        });
        
        // Default to persoon category
        switchCategory('persoon');
        
        console.log('Event listeners set up successfully');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var formData = new FormData(form);
                var data = {};
                for (var pair of formData.entries()) {
                    data[pair[0]] = pair[1];
                }
                console.log('Formulier ingediend:', data);
                alert('Formulier ingediend! (Dit is een test - data wordt niet opgeslagen)\n\nData: ' + JSON.stringify(data, null, 2));
            });
        }
    }
    
    // Initialize when DOM is ready
    console.log('Script loaded, document.readyState:', document.readyState);
    if (document.readyState === 'loading') {
        console.log('Waiting for DOMContentLoaded');
        document.addEventListener('DOMContentLoaded', init);
    } else {
        console.log('DOM already ready, calling init immediately');
        init();
    }
})();


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
    
    function prefillForm(person) {
        var naam = person.naam || {};
        var geboorte = person.geboorte || {};
        var verblijfplaats = person.verblijfplaats || {};
        var bsn = person.burgerservicenummer;
        
        // Toon person display container
        var personDisplay = document.getElementById('person-display');
        if (personDisplay) {
            personDisplay.style.display = 'block';
        }
        
        // Sluit results overlay
        var resultsOverlay = document.getElementById('prefill-results');
        if (resultsOverlay) {
            resultsOverlay.style.display = 'none';
        }
        
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
        
        // Update Verblijfplaats Details
        var detailStraatnaamEl = document.getElementById('detail-straatnaam');
        if (detailStraatnaamEl) detailStraatnaamEl.textContent = verblijfplaats.straatnaam || '-';
        
        var detailHuisnummerEl = document.getElementById('detail-huisnummer');
        if (detailHuisnummerEl) detailHuisnummerEl.textContent = verblijfplaats.huisnummer || '-';
        
        var detailHuisnummertoevoegingEl = document.getElementById('detail-huisnummertoevoeging');
        if (detailHuisnummertoevoegingEl) detailHuisnummertoevoegingEl.textContent = verblijfplaats.huisnummertoevoeging || '-';
        
        var detailPostcodeEl = document.getElementById('detail-postcode');
        if (detailPostcodeEl) detailPostcodeEl.textContent = verblijfplaats.postcode || '-';
        
        var detailWoonplaatsEl = document.getElementById('detail-woonplaats');
        if (detailWoonplaatsEl) detailWoonplaatsEl.textContent = verblijfplaats.woonplaatsnaam || verblijfplaats.woonplaats || '-';
        
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
        }
        
        console.log('Person display updated met persoon data:', person);
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
        
        // Update partner details
        var detailPartnerBsnEl = document.getElementById('detail-partner-bsn');
        if (detailPartnerBsnEl) detailPartnerBsnEl.textContent = partner.burgerservicenummer || '-';
        
        var detailPartnerVoornamenEl = document.getElementById('detail-partner-voornamen');
        if (detailPartnerVoornamenEl) detailPartnerVoornamenEl.textContent = voornamen || '-';
        
        var detailPartnerVoorvoegselEl = document.getElementById('detail-partner-voorvoegsel');
        if (detailPartnerVoorvoegselEl) detailPartnerVoorvoegselEl.textContent = naam.voorvoegsel || '-';
        
        var detailPartnerGeslachtsnaamEl = document.getElementById('detail-partner-geslachtsnaam');
        if (detailPartnerGeslachtsnaamEl) detailPartnerGeslachtsnaamEl.textContent = naam.geslachtsnaam || '-';
        
        // Update burgerlijke staat in overview
        var personStatusEl = document.getElementById('display-person-status');
        if (personStatusEl && partner.burgerservicenummer) {
            personStatusEl.textContent = 'Gehuwd';
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
        
        var kindHtml = '<div class="kind-detail-item" data-index="' + kindIndex + '">';
        kindHtml += '<h4>Kind ' + (kindIndex + 1) + ': ' + escapeHtml(volledigeNaam || 'Onbekend') + '</h4>';
        kindHtml += '<div class="kind-detail-grid">';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">BSN</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(kind.burgerservicenummer || '-') + '</div>';
        kindHtml += '</div>';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">Voornamen</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(voornamen || '-') + '</div>';
        kindHtml += '</div>';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">Voorvoegsel</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(voorvoegsel || '-') + '</div>';
        kindHtml += '</div>';
        kindHtml += '<div class="detail-row">';
        kindHtml += '<div class="detail-label">Geslachtsnaam</div>';
        kindHtml += '<div class="detail-value">' + escapeHtml(geslachtsnaam || '-') + '</div>';
        kindHtml += '</div>';
        kindHtml += '</div>';
        kindHtml += '</div>';
        
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
        
        var natHtml = '<div class="nationaliteit-detail-item" data-index="' + natIndex + '">';
        natHtml += '<h4>Nationaliteit ' + (natIndex + 1) + ': ' + escapeHtml(natData.omschrijving || natData.code || 'Onbekend') + '</h4>';
        natHtml += '<div class="nationaliteit-detail-grid">';
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">Code</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(natData.code || '-') + '</div>';
        natHtml += '</div>';
        natHtml += '<div class="detail-row">';
        natHtml += '<div class="detail-label">Omschrijving</div>';
        natHtml += '<div class="detail-value">' + escapeHtml(natData.omschrijving || '-') + '</div>';
        natHtml += '</div>';
        natHtml += '</div>';
        natHtml += '</div>';
        
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
            }
        });
        
        // Update details title
        var categoryLabels = {
            'persoon': '01. Persoon',
            'ouder1': '02. Ouder 1',
            'ouder2': '03. Ouder 2',
            'nationaliteit': '04. Nationaliteit',
            'partner': '05. Huwelijk/GPS',
            'inschrijving': '07. Inschrijving',
            'verblijfplaats': '08. Verblijfplaats',
            'kinderen': '09. Kinderen'
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
        
        // Submenu items
        var submenuItems = document.querySelectorAll('.submenu-item');
        submenuItems.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.stopPropagation();
                // Update active submenu item
                var parent = item.closest('.category-item');
                if (parent) {
                    var submenuItems = parent.querySelectorAll('.submenu-item');
                    submenuItems.forEach(function(sub) {
                        sub.classList.remove('active');
                    });
                    item.classList.add('active');
                }
                // TODO: Switch tussen Actueel/Historie view
            });
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


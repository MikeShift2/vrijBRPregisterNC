-- Update Personen schema (ID 6) met _embedded veld voor relaties
-- Dit voegt relatie-velden toe volgens Haal Centraal BRP Bevragen API-specificatie

UPDATE oc_openregister_schemas 
SET properties = JSON_MERGE_PRESERVE(
    properties,
    '{
        "_embedded": {
            "type": "object",
            "description": "Embedded relaties volgens Haal Centraal BRP Bevragen API-specificatie",
            "properties": {
                "partners": {
                    "type": "array",
                    "description": "Partners van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Partner object volgens Haal Centraal specificatie"
                    }
                },
                "kinderen": {
                    "type": "array",
                    "description": "Kinderen van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Kind object volgens Haal Centraal specificatie"
                    }
                },
                "ouders": {
                    "type": "array",
                    "description": "Ouders van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Ouder object volgens Haal Centraal specificatie"
                    }
                },
                "nationaliteiten": {
                    "type": "array",
                    "description": "Nationaliteiten van de persoon",
                    "items": {
                        "type": "object",
                        "description": "Nationaliteit object volgens Haal Centraal specificatie"
                    }
                }
            }
        }
    }'
),
updated = NOW()
WHERE id = 6;








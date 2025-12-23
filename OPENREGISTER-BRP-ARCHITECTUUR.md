# Gids: Implementatie van een Open Register bovenop een vrijBRP-database

## Inleiding: De noodzaak voor een moderne BRP-architectuur

Gemeenten die gebruikmaken van bestaande BRP-systemen zoals vrijBRP, staan voor de strategische noodzaak om hun informatievoorziening te moderniseren volgens de principes van Common Ground. Deze transitie is essentieel om vendor lock-in te doorbreken, interoperabiliteit te garanderen en een wendbare, toekomstbestendige architectuur te realiseren. De implementatie van een Open Register bovenop de bestaande database is een bewuste architectonische keuze om deze modernisering te realiseren. Door data los te koppelen van applicaties en te ontsluiten via gestandaardiseerde API's, wordt de basis gelegd voor een fundamenteel robuustere gemeentelijke informatievoorziening. Dit document biedt een diepgaande technische analyse van de architectuur, de implementatiestrategie en de praktische validatie van dit model.

## 1. Fundamenten: De Common Ground-architectuurvisie

De Common Ground-visie is gebaseerd op een conceptueel model van vijf lagen dat de ontkoppeling van systemen afdwingt. Dit gelaagde model is cruciaal om te begrijpen waar de verschillende componenten van de BRP-architectuur hun plaats hebben en hoe ze met elkaar interageren. Het register is hierin de bron van waaruit de gehele informatieketen wordt gevoed.

### 1.1. De vijf lagen van Common Ground

Het model structureert de informatievoorziening in de volgende vijf lagen, van de eindgebruiker tot de ruwe data:

1. **Laag 5: Interactie (Interaction)**  
   Dit is de presentatielaag waar de eindgebruiker (burger of medewerker) interactie heeft met het proces en de data.  
   *Voorbeeld BRP-context:* De schermen en interfaces die een ambtenaar van burgerzaken gebruikt om een geboorteaangifte te verwerken.

2. **Laag 4: Processen (Processes)**  
   Hier bevinden zich de applicaties die de gegevens gebruiken om gemeentelijke processen uit te voeren, zoals zaakgericht werken of veldwerkapplicaties.  
   *Voorbeeld BRP-context:* Een Zaakgericht Werken (ZGW)-systeem dat het proces 'Verhuizing' orkestreert.

3. **Laag 3: Diensten (Services)**  
   Dit zijn de gestandaardiseerde API's (zoals de Haal Centraal API's) en de domeinspecifieke logica-services die gegevens en functionaliteit beschikbaar maken voor andere applicaties.  
   *Voorbeeld BRP-context:* De 'Haal Centraal BRP Bevragen API' of de gespecialiseerde 'vrijBRP Logica Service'.

4. **Laag 2: Componenten (Components)**  
   Dit is de software die de gegevens ontsluit, beheert en beveiligt. De register-applicatie die gestandaardiseerde API's aanbiedt, bevindt zich hier.  
   *Voorbeeld BRP-context:* De Open Register-applicatie die de API's aanbiedt en de data uit Laag 1 beheert.

5. **Laag 1: Gegevens (Data)**  
   Dit is de bron van de informatie waar de entiteiten (zoals 'Persoon' of 'Adres') en hun attributen fysiek worden opgeslagen.  
   *Voorbeeld BRP-context:* De PostgreSQL-databasetabellen van de vrijBRP-applicatie.

### 1.2. Positionering van de BRP-componenten in het model

Op basis van de voorgestelde architectuur worden de BRP-componenten als volgt in het vijflagenmodel gepositioneerd:

| Component | Common Ground-laag |
|-----------|-------------------|
| De bestaande vrijBRP PostgreSQL-database | Laag 1: Gegevens (Data) |
| De Open Register-applicatie | Laag 2: Componenten (Components) |
| De Haal Centraal BRP Bevragen API | Laag 3: Diensten (Services) |
| De te ontwikkelen 'vrijBRP Logica Service' | Laag 3: Diensten (Services) |
| Een Zaakgericht Werken (ZGW)-systeem | Laag 4: Processen (Processes) |
| De schermen/interfaces voor ambtenaren | Laag 5: Interactie (Interaction) |

### 1.3. Kernprincipes: API-first en data bij de bron

De architectuur zoals hierboven beschreven brengt twee fundamentele Common Ground-principes in de praktijk. Het API-first-principe wordt afgedwongen doordat alle data-uitwisseling verloopt via gestandaardiseerde API's (Laag 3), waardoor applicaties (Laag 4 en 5) niet langer direct gekoppeld zijn aan de onderliggende database (Laag 1). Deze scheiding is non-negotiable.

Het principe van gegevens bij de bron wordt gewaarborgd doordat het Open Register (Laag 2) fungeert als de enige, gezaghebbende bronregistratie voor BRP-gegevens. Applicaties halen de data direct op bij deze bron en hoeven geen lokale kopieën meer te onderhouden, wat de datakwaliteit en actualiteit radicaal verbetert. De volgende sectie analyseert in detail waarom het Open Register de aangewezen component is om deze cruciale rol te vervullen.

## 2. Kerncomponent: Open Register als API-laag voor vrijBRP

De strategische keuze om Open Register als een gestandaardiseerde laag bovenop de bestaande vrijBRP-database te plaatsen, is meer dan een technologische beslissing. Het is een fundamentele investering in interoperabiliteit, architectonische duurzaamheid en het vermogen om flexibel in te spelen op toekomstige ontwikkelingen in het overheidsdatalandschap.

### 2.1. Voordelen van de Open Register-architectuur

Het inzetten van Open Register als een complete componentenlaag (Laag 2) biedt significante voordelen ten opzichte van het direct bouwen van een API op een pure PostgreSQL-database.

- **Interoperabiliteit en standaardisatie**  
  Open Register ontsluit data via gestandaardiseerde API-specificaties, zoals de Haal Centraal BRP Bevragen API. Dit garandeert dat elke afnemer die de standaard implementeert, direct kan aansluiten. Het creëert een perfecte scheiding (decoupling), waardoor de onderliggende vrijBRP-database kan worden gewijzigd of vervangen zonder dat de afnemende applicaties dit merken.

- **Out-of-the-box functionaliteit**  
  Een Open Register-implementatie biedt essentiële functionaliteiten die anders in kostbare maatwerkcode ontwikkeld moeten worden. De ingebouwde eventing genereert automatisch notificaties bij elke mutatie, cruciaal voor synchronisatie met andere systemen. De functie voor historie/versies zorgt voor een volledige audit trail van elke wijziging, een dwingende voorwaarde voor een basisregistratie als de BRP.

- **Common Ground-gereedheid**  
  Open Register is ontworpen om direct te integreren met de Nederlandse API Exchange (NLX), de standaard transport- en autorisatielaag binnen Common Ground. Dit vereenvoudigt het beheer van autorisaties, logging en beveiliging aanzienlijk. Door aan te sluiten op het open source-ecosysteem wordt bovendien geprofiteerd van collectieve doorontwikkeling en kennisdeling binnen de community.

### 2.2. Architectonische afwegingen en beheerde risico's

Een robuuste architectuur is het resultaat van bewuste afwegingen. Het Open Register-model prioriteert lange-termijninteroperabiliteit en governance boven korte-termijn operationele eenvoud. De geassocieerde kosten en complexiteiten moeten actief worden beheerd.

- **Operationele en infrastructuurcomplexiteit**  
  De architectuur introduceert meer 'moving parts': naast de vrijBRP-applicatie en de database moet ook de Open Register-component worden beheerd en gemonitord. Het initieel configureren van de datamapping tussen het gestandaardiseerde Open Register-model en de interne vrijBRP-tabelstructuur is een arbeidsintensief en kritiek traject.

- **Performance en directe data-toegang**  
  Een API-call introduceert een geringe latency-overhead ten opzichte van een directe SQL-query op de database. De kritieke afweging hier is het verlies van SQL-flexibiliteit. Complexe interne rapportages die nu gebruikmaken van joins over tientallen tabellen kunnen niet langer rechtstreeks op de database worden uitgevoerd en moeten worden herontworpen om via de gestandaardiseerde API's te werken.

- **Afhankelijkheid en governance**  
  Er ontstaat een technologische afhankelijkheid van de Open Register-software. Hoewel dit open source is, vereist het beheer en onderhoud specifieke expertise. Bovendien dwingt de structuur van een bronregistratie de organisatie tot een hogere discipline op het gebied van datagovernance en processtandaardisatie.

Deze nadelen zijn geen onvoorziene obstakels, maar de gecalculeerde investeringskosten die nodig zijn om de strategische winst van Common Ground te behalen. De volgende secties beschrijven de concrete stappen om deze investering te realiseren.

## 3. Implementatiestrategie Deel 1: Lezen van BRP-data

Het implementeren van de leesfunctionaliteit is de eerste cruciale stap. Hierbij wordt de Haal Centraal BRP Bevragen API als leidende standaard gebruikt, wat direct zorgt voor interoperabiliteit binnen het Nederlandse overheidslandschap.

### 3.1. Stap 1: Definiëren van het Open Register-datamodel

Het doel van deze stap is het configureren van de 'Schema's' binnen Open Register zodat deze exact overeenkomen met de specificaties van de Haal Centraal API. Dit is precisiewerk waarbij veldnamen, datatypes en de relaties tussen entiteiten (kardinaliteit) strikt de standaard moeten volgen. Elke afwijking van de Haal Centraal-standaard in veldnamen of datatypes doorbreekt onmiddellijk de interoperabiliteit en invalideert het primaire doel van deze architectuur.

### 3.2. Stap 2: Implementeren van de databasemapping

Dit is de meest arbeidsintensieve stap van het traject. In deze fase wordt de configuratie van Open Register gebruikt om de velden uit het datamodel (stap 1) te koppelen aan de specifieke tabellen en kolommen in de onderliggende vrijBRP PostgreSQL-database. Dit is de 'vertalingslaag' die de interne, applicatie-specifieke datastructuur transformeert naar de externe, gestandaardiseerde API-structuur. Deze mapping vormt de kritieke abstractielaag die de externe, gestandaardiseerde wereld ontkoppelt van de interne, propriëtaire databasestructuur van vrijBRP, en biedt daarmee architectonische vrijheid op de lange termijn.

Een concreet voorbeeld: Het veld `Persoon.voorletters` in het Open Register wordt gemapt naar de corresponderende kolom in de vrijBRP-database.

### 3.3. Stap 3: Configureren van endpoints en bevragingen

De laatste configuratiestap omvat het instellen van de API-endpoints, zoals `/personen`, en het waarborgen dat de queryparameters (zoals filters en sortering) correct worden verwerkt, exact zoals de Haal Centraal-standaard voorschrijft. Na voltooiing van deze stap is de BRP-data op een gestandaardiseerde manier leesbaar voor alle geautoriseerde afnemers. Nu de leeskant is gedefinieerd, richt de gids zich op de aanzienlijk complexere uitdaging van de schrijfkant: de BRP-mutaties.

## 4. Implementatiestrategie Deel 2: Architectuur voor BRP-mutaties

Het verwerken van BRP-mutaties conform de eisen van de Rijksdienst voor Identiteitsgegevens (RVIG) en de Common Ground-principes vereist een heldere architectuur. Het cruciale inzicht hierbij is de strikte scheiding van de BRP-domeinlogica (validatieregels) en de data-opslag (het Open Register). Het Open Register is een generiek platform; de zeer specifieke en complexe BRP-regels horen thuis in een gespecialiseerde service.

### 4.1. De scheiding van verantwoordelijkheden

Het BRP-domein kent drie soorten logica die elk een eigen plek in de architectuur moeten krijgen om een onderhoudbaar en schaalbaar systeem te garanderen.

| Type logica | Verantwoordelijkheid | Plek in ecosysteem |
|-------------|---------------------|-------------------|
| Datavalidatie/RVIG-eisen | Afhandelen van complexe, domein-specifieke BRP-regels, consistentiechecks en correcte historie-afhandeling. | vrijBRP Logica Service |
| Gegevensopslag & ontsluiting | Opslaan van data, versiebeheer, genereren van events, en het aanbieden van de gestandaardiseerde Haal Centraal lees-API. | Open Register |
| Proceslogica/workflow | Bepalen in welke stap van een zaak (bv. 'Verhuizing') een mutatie mag plaatsvinden en wie daartoe bevoegd is. | Zaakgericht Werken (ZGW)-systeem |

### 4.2. De bron van de waarheid: RVIG-logica versus wetstekst

Het is essentieel om het verschil te begrijpen tussen de juridische bronteksten op regels.overheid.nl en de uitvoerbare technische validatieregels. De wetsteksten beschrijven de juridische norm, maar de daadwerkelijke implementatie van de BRP-logica is afgeleid van het Logisch Ontwerp BRP (LO BRP) en de Handboeken van de RVIG (zoals de Handleiding Uitvoeringsprocedures - HUP), die de gedetailleerde, technische specificaties bevatten die software moet afdwingen. Dit bevestigt de noodzaak van een gespecialiseerde vrijBRP Logica Service, omdat deze regels te complex en specifiek zijn voor een generiek registerplatform zoals Open Register.

### 4.3. Dataflow van een mutatie (Scenario A)

Het stapsgewijze proces voor een BRP-mutatie verloopt in dit model als volgt:

1. **Trigger:** Een Zaak-systeem (Laag 4) stuurt een mutatieverzoek naar de API van het Open Register (Laag 2) nadat een processtap, zoals een goedkeuring, is voltooid.

2. **Coördinatie:** Het Open Register valideert de autorisatie van het verzoek en fungeert als coördinator door het verzoek door te sturen naar de vrijBRP Logica Service (Laag 3).

3. **Validatie en transformatie:** De vrijBRP Logica Service voert alle complexe RVIG-checks uit. Cruciaal is dat het de data ook transformeert van het API-formaat naar de correcte, persistente BRP-datastructuur, inclusief de juiste metadata voor historie.

4. **Persistency & eventing:** De vrijBRP Logica Service stuurt de volledig gevalideerde en getransformeerde data terug. Het Open Register heeft nu een puur persisterende taak: het slaat de data atomair op in de database (Laag 1), beheert de versiehistorie, en genereert een mutatie-event voor alle geabonneerde afnemers.

Dit model vormt de basis voor een robuuste, auditeerbare en Common Ground-conforme BRP-architectuur. De volgende sectie illustreert hoe dit in de praktijk werkt.

## 5. De architectuur in de praktijk: PoC 'Aangifte van Geboorte'

Om de volledige architectuur te valideren, wordt het proces 'Aangifte van Geboorte' als Proof of Concept (PoC) gebruikt. Dit proces is bij uitstek geschikt omdat het de architectuur op meerdere fronten tegelijk test: de transactiezuiverheid bij het aanmaken van meerdere entiteiten, het leggen van complexe relaties, het correct vastleggen van historie, de koppeling van documenten en het valideren van het "lees-voor-schrijven"-patroon door de gegevens van de ouders op te halen voordat de mutatie plaatsvindt.

### 5.1. De rol van elke laag in het proces

De aangifte doorloopt de architectuur in een heldere, chronologische volgorde:

1. **Interactie & processtart (Laag 5 & 4):** Een ambtenaar gebruikt de interface om een zaak 'Aangifte Geboorte' te starten in het ZGW-systeem.

2. **Dataverrijking (Lees-actie via Laag 3 & 2):** Het ZGW-systeem roept de Haal Centraal API aan om de gegevens van de ouders op te halen uit het Open Register en deze te verifiëren.

3. **Procesafhandeling & mutatietrigger (Laag 4):** Na het bijvoegen van de geboorteakte en het verkrijgen van goedkeuring, triggert het ZGW-systeem de definitieve mutatie-call naar de API van het Open Register.

4. **Validatie & coördinatie (Laag 2 & 3):** Het Open Register coördineert de mutatie door de vrijBRP Logica Service aan te roepen voor de RVIG-validatie en de datatransformatie.

5. **Opslag & notificatie (Laag 2 & 1):** Na succesvolle validatie slaat het Open Register de nieuwe records persistent op in de PostgreSQL-database en publiceert een 'Geboorte-Event' voor geabonneerde systemen.

### 5.2. Kritieke mijlpalen voor de PoC-validatie

De succesvolle uitvoering van deze PoC valideert vier kritieke mijlpalen van de architectuur:

1. **De leeskant (Bevragen):** Het correct ophalen van de oudergegevens via de Haal Centraal API valideert de gehele lees-pijplijn, van interface tot database.

2. **De validatie (Logica):** De succesvolle aanroep van de vrijBRP Logica Service en de correcte afhandeling van de RVIG-checks bewijst de cruciale scheiding van data en logica.

3. **De transactie (Schrijven & eventing):** Het atomair aanmaken van de nieuwe persoon, de relaties en het direct genereren van een event valideert de kernfunctionaliteit van het Open Register als bronregistratie.

4. **De procesborging (ZGW):** Het correct afhandelen van de zaak en het koppelen van de geboorteakte valideert de rol van de proceslaag en de compliance met de ZGW-standaarden.

## 6. Conclusie en aanbevelingen

De transitie naar een architectuur gebaseerd op Open Register is een strategische investering in een flexibele, interoperabele en toekomstbestendige informatievoorziening. Door de data te scheiden van applicatielogica en processen, wordt een fundament gelegd waarop snel en efficiënt kan worden ingespeeld op de veranderende behoeften van de gemeente en haar inwoners.

### Samenvatting architectonische keuzes

De kern van de voorgestelde architectuur bestaat uit de volgende beslissingen:

- Gebruik Open Register als de gestandaardiseerde API-laag (Componentenlaag 2) bovenop de bestaande database.
- Ontsluit alle leesacties via de Haal Centraal BRP Bevragen API om interoperabiliteit te garanderen.
- Isoleer de complexe, domein-specifieke BRP-mutatielogica in een aparte vrijBRP Logica Service (Dienstenlaag 3).
- Orkestreer alle bedrijfsprocessen die leiden tot een mutatie via een Zaakgericht Werken (ZGW)-systeem (Proceslaag 4).

### De cruciale afhankelijkheid

De grootste uitdaging in deze architectuur is niet louter technisch, maar een cruciale contractuele en organisatorische afhankelijkheid. Het succes van het gehele model hangt af van de bereidheid van de vrijBRP-leverancier om hun monolithische applicatie te herstructureren. De BRP-logica moet worden ontkoppeld en beschikbaar worden gemaakt als een bevragbare API-service. Deze transformatie is de spil waar de architectuur om draait en moet daarom een primair focuspunt zijn voor project governance.

### Eindaanbeveling

Het wordt aanbevolen om te starten met de 'Aangifte van Geboorte' als een gerichte Proof of Concept (PoC). Dit complexe proces stelt alle lagen van de architectuur op de proef en biedt de mogelijkheid om de technische haalbaarheid te bewijzen. Belangrijker nog, een succesvolle PoC maakt de organisatorische voordelen van een ontkoppelde, API-first-architectuur direct tastbaar en creëert het draagvlak dat nodig is voor een volledige implementatie.








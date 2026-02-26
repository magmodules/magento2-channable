# Retouren

Deze gids legt uit hoe retouren werken in de [Channable module](https://www.magmodules.eu/nl/magento2-channable.html). Het behandelt de volledige retourstroom — van een klant die een retour aanvraagt op een marktplaats, tot de retour in uw Magento admin, tot het aanmaken van een creditnota. Of u retouren handmatig verwerkt of het hele proces wilt automatiseren, op deze pagina vindt u alle informatie.

## Hoe Retouren Werken

Wanneer een klant een retour aanvraagt op een marktplaats (bijv. bol, Amazon), pikt Channable dit op en stuurt het via een webhook naar uw Magento-webshop. De retour verschijnt in het **Channable → Retouren** overzicht in de admin, gekoppeld aan de originele bestelling.

Van daaruit kunt u:
- De retourgegevens bekijken (artikel, reden, klant)
- De status bijwerken (accepteren, afwijzen, repareren, omruilen, etc.)
- Een creditnota aanmaken — handmatig of automatisch

De status die u instelt wordt teruggestuurd naar Channable, dat vervolgens de marktplaats bijwerkt.

## Retouren Instellen

**Locatie:** Winkels → Configuratie → Channable → Retouren

### Inschakelen

Schakelt de retourfunctie in of uit per storeview.

### Webhooks

Na het inschakelen wordt er een webhook-URL gegenereerd voor elke storeview. Kopieer deze URL en plak deze in uw Channable-account bij de marktplaatskoppeling. Zorg ervoor dat u de volledige URL kopieert — deze is lang en kan gedeeltelijk verborgen zijn.

**Formaat:** `{base_url}/channable/returns/hook/store/{store_id}/code/{token}`

### Retourblok Tonen op Creditnotapagina

Wanneer ingeschakeld verschijnt er een retourblok op de creditnota-aanmaakpagina voor Channable-bestellingen met een openstaande retour. Hiermee kunt u handmatig selecteren welke retour(en) u wilt koppelen bij het aanmaken van de creditnota.

### Retouren Automatisch Koppelen

Wanneer ingeschakeld wordt elke openstaande retour automatisch als "geaccepteerd" gemarkeerd wanneer u een creditnota aanmaakt voor die bestelling. Dit overschrijft de handmatige selectie in het retourblok.

**Wanneer gebruiken:** U heeft vaste processen voor het afhandelen van creditnota's en hoeft niet elke retour handmatig te beoordelen.

### Creditnota voor Afgeronde Retouren

Wanneer ingeschakeld wordt automatisch een creditnota aangemaakt voor retouren die binnenkomen met de status "complete". Dit zijn retouren die al afgehandeld zijn door de marktplaats zelf — de module spiegelt dit alleen in Magento.

## Retourstatussen

| Status | Betekenis |
|---|---|
| New | Zojuist geïmporteerd, wacht op actie |
| Accepted | Retour goedgekeurd, terugbetaling wordt verwerkt |
| Rejected | Retour afgewezen |
| Repaired | Artikel wordt gerepareerd en teruggestuurd |
| Exchanged | Artikel wordt vervangen door een nieuw exemplaar |
| Keeps | Klant behoudt het artikel (geen retourzending) |
| Cancelled | Retourproces is geannuleerd |
| Complete | Volledig afgehandeld aan marktplaatszijde |

## Retourenoverzicht

**Locatie:** Channable → Retouren

Het overzicht toont alle geïmporteerde retouren met de volgende informatie:

**Kolommen:**
- Winkel, Magento Bestelling, Creditnota
- Kanaal Retour-ID, Kanaal Bestelling-ID
- Klantnaam
- Artikel (weergegeven als "Aantal x Titel (GTIN)")
- Reden (inclusief opmerkingen van de klant)
- Bestelling Creditnota's (aantal)
- Bestelstatus
- Importdatum
- Status

### Rijacties

Wanneer een retour de status "new" heeft, kunt u deze direct vanuit het overzicht op een andere status zetten: Accepteren, Afwijzen, Repareren, Omruilen, Behouden of Annuleren.

### Massacties

- **Opnieuw verwerken** — Koppelt geselecteerde retouren opnieuw aan hun Magento-bestellingen (handig als de bestelling niet gevonden werd tijdens de eerste import)
- **Creditnota Aanmaken** — Maakt een creditnota aan voor de gekoppelde bestelling
- **Creditnota Aanmaken + Accepteren** — Maakt een creditnota aan en zet de retourstatus op "accepted"
- **Verwijderen** — Verwijdert de retour uit Magento (wordt niet bijgewerkt in Channable)

## Creditnota-integratie

De module kan op drie manieren creditnota's aanmaken vanuit retouren:

### 1. Handmatig via Massacties
Selecteer retouren in het overzicht → "Creditnota Aanmaken" massactie. De module zoekt het bestelregel op basis van het GTIN uit de retourdata en het product-SKU (of geconfigureerd GTIN-attribuut).

### 2. Retourblok op Creditnotapagina
Wanneer "Retourblok Tonen op Creditnotapagina" is ingeschakeld, ziet u een blok met selectievakjes op de creditnota-aanmaakpagina. Selecteer welke retouren u wilt koppelen, en bij het opslaan van de creditnota worden deze retouren op "accepted" gezet.

### 3. Volledig Automatisch
Schakel zowel "Retouren Automatisch Koppelen" als "Creditnota voor Afgeronde Retouren" in. Retouren die binnenkomen als "complete" krijgen automatisch een creditnota. Retouren met status "new" worden als "accepted" gemarkeerd zodra u een creditnota aanmaakt voor hun bestelling.

## GTIN-koppeling

Bij het aanmaken van een creditnota vanuit een retour moet de module het geretourneerde artikel koppelen aan een bestelregel. Dit gebeurt op basis van het GTIN (barcode) uit de retourdata.

Het GTIN-attribuut wordt geconfigureerd onder: Winkels → Configuratie → Channable → Feed → GTIN Attribuut

Opties:
- **SKU** (standaard) — koppelt direct op SKU
- **EAN/barcode-attribuut** — koppelt op een aangepast productattribuut
- **Product-ID** — gebruikt het numerieke product-ID

### Product-ID Fallback

Sommige marktplaatsen sturen geen GTIN mee in hun retourdata. Wanneer het geconfigureerde GTIN-attribuut geen match oplevert en de waarde numeriek is, probeert de module automatisch het product te laden op basis van het entity-ID.

Als noch de attribuutkoppeling noch de ID-fallback een product vindt, mislukt het aanmaken van de creditnota en wordt er een fout gelogd.

## Testretouren

U kunt testretouren aanmaken vanuit het adminoverzicht via de "Simuleer" knop. Dit maakt een retour aan met willekeurige productdata (of een specifieke bestelling indien geconfigureerd) zonder dat er een echte Channable-webhook nodig is. Handig om uw configuratie te testen voordat u live gaat.

---

## Meer Hulp Nodig?

**Documentatie:**
- [Alle Help Artikelen](https://www.magmodules.eu/nl/help/channable/) - Compleet documentatie overzicht

**Support:**
- [Contact Opnemen](https://www.magmodules.eu/nl/support/) - Hulp van ons team

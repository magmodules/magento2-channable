# Verzendingen

Deze gids legt uit hoe verzendupdates werken in de [Channable module](https://www.magmodules.eu/nl/magento2-channable.html). Het behandelt wat Magento terugstuurt naar Channable wanneer u een bestelling verzendt, hoe track & trace en retourlabels worden herkend, en hoe het pakbonnummer werkt — inclusief het meegeven van uw eigen nummer vanuit een ERP-systeem.

## Hoe Verzendupdates Werken

Channable vraagt periodiek aan uw Magento-webshop welke bestellingen verzonden zijn. De module antwoordt met een fulfillment-payload per verzending, met daarin de track & trace-gegevens en het pakbonnummer. Channable geeft dit door aan de marktplaats, die de klant informeert.

Alles is gebaseerd op standaard Magento-verzendingen. Zodra er een verzending bestaat — handmatig aangemaakt in de admin, door een verzendextensie zoals SendCloud, MyParcel of Paazl, of via de REST API — wordt deze automatisch opgepikt. Extra configuratie is niet nodig.

Een verzendupdate bevat:

| Veld | Bron |
|---|---|
| `tracking_code` | De trackingnummers van de verzending |
| `carrier_code` | De vervoerder van elke trackingregel |
| `title` | De titel van elke trackingregel |
| `return_tracking_code` | Trackingnummers die herkend zijn als retourlabel |
| `return_transporter` | De vervoerder van die retourlabels |
| `delivery_bill_id` | Het nummer van de pakbon in het pakket |
| `shipment_id` | Het Magento verzendingsnummer |

Bestellingen met meerdere verzendingen leveren één update per verzending op. Elke verzending behoudt zijn eigen trackinggegevens en zijn eigen pakbonnummer.

## Pakbonnummer (Delivery Bill ID)

Sommige marktplaatsen — waaronder Conrad — factureren zelf aan de klant terwijl u het pakket verstuurt. Om de klant die factuur te laten koppelen aan het ontvangen pakket, wordt het nummer van de pakbon in het pakket meegestuurd met de verzending. Dat nummer is het pakbonnummer.

### Standaard: het Magento verzendingsnummer

Standaard stuurt de module het Magento verzendingsnummer mee (bijvoorbeeld `300000004`). Dit werkt zonder configuratie en vraagt geen handmatige invoer:

- Het is direct zichtbaar in de admin zodra een verzending is aangemaakt
- Het staat in de verzendbevestigingsmail
- Het wordt afgedrukt op de standaard pakbon van Magento, waardoor het nummer op het papier in het pakket overeenkomt met het nummer dat de marktplaats ontvangt

Voor webshops die vanuit Magento verzenden is dit voldoende.

### Uw eigen nummer uit een ERP-systeem gebruiken

Drukt u uw pakbonnen af vanuit een ERP- of fulfillmentsysteem, dan genereert dat systeem een eigen pakbonnummer (bijvoorbeeld `DSN-123`). In dat geval moet de marktplaats uw nummer ontvangen en niet dat van Magento, anders komt het nummer op de factuur niet overeen met het papier in de doos.

Uw ERP kan het nummer meegeven via de extension attribute `channable_delivery_bill_id` op de verzending. Dat kan op twee manieren.

**Bij het aanmaken van de verzending** — voeg het attribuut toe aan de shipment arguments:

```
POST /rest/V1/order/{orderId}/ship

{
  "tracks": [
    {
      "carrier_code": "dhl",
      "title": "DHL",
      "track_number": "3S123456789"
    }
  ],
  "arguments": {
    "extension_attributes": {
      "channable_delivery_bill_id": "DSN-123"
    }
  }
}
```

**Op een bestaande verzending** — handig wanneer een verzendextensie de verzending al heeft aangemaakt:

```
POST /rest/V1/shipment

{
  "entity": {
    "entity_id": 12,
    "order_id": 34,
    "extension_attributes": {
      "channable_delivery_bill_id": "DSN-123"
    }
  }
}
```

De waarde wordt opgeslagen op de verzending en weer teruggegeven door `GET /rest/V1/shipment/{id}`, zodat u altijd kunt controleren wat er is meegestuurd.

Is het veld leeg, dan valt de module terug op het Magento verzendingsnummer. Verzendingen die zijn aangemaakt door verzendextensies die dit veld niet kennen, blijven dus gewoon werken zoals voorheen.

**Belangrijk:** het nummer dat u meegeeft moet hetzelfde nummer zijn als dat op de pakbon in het pakket. Daar is het veld voor bedoeld.

## Retourlabels

Verzendextensies voegen vaak een tweede trackingregel toe voor het retourlabel. De module kan die regels herkennen en apart rapporteren als `return_tracking_code` in plaats van als gewone trackingcode.

**Locatie:** Winkels → Configuratie → Channable → Marketplace → Bestellingen

### Herkenning van retourlabels

Kies hoe retourlabels worden herkend. Bij patroonherkenning bepaalt u per vervoerder welke trackingtitels op een retourlabel wijzen.

### Retourlabel-patronen

Een tabel met twee kolommen:

- **Vervoerder** — de vervoerder waarvoor de regel geldt, of "all" voor elke vervoerder
- **Titelpatroon** — de tekst die in de trackingtitel moet voorkomen, bijvoorbeeld `Return` of `Retour`

Een trackingregel die aan een van deze regels voldoet, wordt als retourlabel verstuurd. Alle andere regels worden als gewone trackingcode verstuurd.

## Meerdere Verzendingen Per Bestelling

Wordt een bestelling in delen verzonden, dan wordt elke verzending apart gerapporteerd, met eigen trackinggegevens, een eigen `shipment_id` en een eigen pakbonnummer. Zo kan de marktplaats de klant precies vertellen welke artikelen in welk pakket zaten.

Gebruikt u eigen pakbonnummers, zorg er dan voor dat elke deelzending een eigen nummer krijgt — hetzelfde nummer op twee pakketten maakt de factuur onmogelijk te koppelen.

## Controleren Wat Er Wordt Verstuurd

De verzendgegevens die Channable ontvangt zijn op te vragen via de webhook-URL's van de module, te vinden onder Winkels → Configuratie → Channable → Marketplace. Door de shipments-URL in uw browser te openen ziet u precies wat Channable ziet, inclusief het pakbonnummer per verzending. Dit is de snelste manier om te controleren of uw ERP-koppeling het veld correct vult.

---

## Meer Hulp Nodig?

**Documentatie:**
- [Alle Help Artikelen](https://www.magmodules.eu/nl/help/channable/) - Compleet documentatie overzicht

**Support:**
- [Contact Opnemen](https://www.magmodules.eu/nl/support/) - Hulp van ons team

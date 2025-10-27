/**
 * Parse KML content and extract polygon coordinates
 */
export function parseKMLToCoordinates(kmlContent: string): [number, number][] | null {
    try {
        const parser = new DOMParser();
        const xmlDoc = parser.parseFromString(kmlContent, 'text/xml');

        const coordinatesElement = xmlDoc.getElementsByTagName('coordinates')[0];
        if (!coordinatesElement) {
            return null;
        }

        const coordText = coordinatesElement.textContent?.trim();
        if (!coordText) {
            return null;
        }

        // KML format: lng,lat,alt (altitude is optional)
        // Split by whitespace and parse each coordinate
        const points = coordText.split(/\s+/);
        const coordinates: [number, number][] = [];

        for (const point of points) {
            const parts = point.trim().split(',');
            if (parts.length >= 2) {
                const lng = parseFloat(parts[0]);
                const lat = parseFloat(parts[1]);
                if (!isNaN(lng) && !isNaN(lat)) {
                    coordinates.push([lat, lng]); // Leaflet uses [lat, lng]
                }
            }
        }

        return coordinates.length > 0 ? coordinates : null;
    } catch (error) {
        console.error('Error parsing KML:', error);
        return null;
    }
}

/**
 * Convert polygon coordinates to KML format
 */
export function coordinatesToKML(coordinates: [number, number][]): string {
    if (!coordinates || coordinates.length === 0) {
        throw new Error('No coordinates provided');
    }

    // Ensure polygon is closed (first point = last point)
    const coords = [...coordinates];
    if (coords[0][0] !== coords[coords.length - 1][0] || coords[0][1] !== coords[coords.length - 1][1]) {
        coords.push(coords[0]);
    }

    // Convert to KML format: lng,lat,0
    const kmlCoordinates = coords.map(([lat, lng]) => `${lng},${lat},0`).join(' ');

    const kml = `<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  <Document>
    <name>Restaurant Geofence</name>
    <Placemark>
      <name>Delivery Area</name>
      <Polygon>
        <outerBoundaryIs>
          <LinearRing>
            <coordinates>${kmlCoordinates}</coordinates>
          </LinearRing>
        </outerBoundaryIs>
      </Polygon>
    </Placemark>
  </Document>
</kml>`;

    return kml;
}

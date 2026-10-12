/**
 * Utilidades geoespaciales para LogiMap
 * Algoritmo Point-in-Polygon (Ray casting) y detección automática de distritos
 */

function isPointInRing(pt, ring) {
  let inside = false
  for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
    const xi = ring[i][0]
    const yi = ring[i][1]
    const xj = ring[j][0]
    const yj = ring[j][1]
    const intersect = ((yi > pt[1]) !== (yj > pt[1])) &&
      (pt[0] < (xj - xi) * (pt[1] - yi) / (yj - yi) + xi)
    if (intersect) inside = !inside
  }
  return inside
}

/**
 * Encuentra a qué distrito pertenece una coordenada [lat, lng]
 * @param {number} lat Latitud
 * @param {number} lng Longitud
 * @param {object} geojson Objeto GeoJSON de distritos
 * @param {string} districtKey Clave de propiedad ('name' o 'macro_vige')
 * @returns {string|null} Nombre del distrito o null
 */
export function findDistrictForCoordinates(lat, lng, geojson, districtKey = 'name') {
  if (!geojson || !geojson.features) return null

  const pt = [lng, lat]
  for (const f of geojson.features) {
    const g = f.geometry
    const getPropName = () => {
      if (districtKey && f.properties[districtKey]) {
        return f.properties[districtKey]
      }
      return f.properties.macro_ante || f.properties.name || f.properties.macro_vige || null
    }

    if (g.type === 'Polygon') {
      if (isPointInRing(pt, g.coordinates[0])) {
        return getPropName()
      }
    } else if (g.type === 'MultiPolygon') {
      for (const poly of g.coordinates) {
        if (isPointInRing(pt, poly[0])) {
          return getPropName()
        }
      }
    }
  }
  return null
}

/**
 * Obtener dirección aproximada mediante OpenStreetMap Nominatim (opcional/asíncrono)
 */
export async function reverseGeocode(lat, lng) {
  try {
    const url = `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`
    const res = await fetch(url, {
      headers: { 'Accept-Language': 'es' },
    })
    const data = await res.json()
    return data.display_name || null
  } catch (e) {
    return null
  }
}

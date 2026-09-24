import { useEffect } from 'react';
import { CircleMarker, MapContainer, Popup, TileLayer, useMap } from 'react-leaflet';
import type { MapMarker } from '../types/maps';
import { MARKER_TYPE_COLORS, MARKER_TYPE_LABELS, SECTOR_LABELS } from '../types/maps';
import 'leaflet/dist/leaflet.css';

const DEFAULT_CENTER: [number, number] = [-2.7246, 121.5295];
const DEFAULT_ZOOM = 10;

interface MapCenterProps {
    center: [number, number] | null;
    zoom: number;
}

function MapCenter({ center, zoom }: MapCenterProps): null {
    const map = useMap();

    useEffect(() => {
        if (center) {
            map.setView(center, zoom);
        }
    }, [map, center, zoom]);

    return null;
}

function markerColor(markerType: string): string {
    return MARKER_TYPE_COLORS[markerType] ?? '#6366f1';
}

function markerRadius(markerType: string): number {
    return markerType === 'school' || markerType === 'health_facility' ? 7 : 6;
}

interface MapViewProps {
    markers: MapMarker[];
    center?: [number, number] | null;
    zoom?: number;
    className?: string;
}

/**
 * Leaflet map rendering coloured circles per marker type with a detail popup.
 * The viewport recentres (via a watcher child) when new coordinate bounds or a
 * selected location change.
 */
export function MapView({ markers, center = null, zoom = DEFAULT_ZOOM, className = 'h-[480px]' }: MapViewProps): React.JSX.Element {
    return (
        <MapContainer center={DEFAULT_CENTER} zoom={DEFAULT_ZOOM} scrollWheelZoom className={`rounded-2xl border border-gray-200 ${className}`}>
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            <MapCenter center={center} zoom={zoom} />
            {markers.map((marker) => (
                <CircleMarker
                    key={`${marker.type}-${marker.id}`}
                    center={[marker.latitude, marker.longitude]}
                    radius={markerRadius(marker.type)}
                    pathOptions={{ color: '#ffffff', weight: 2, fillColor: markerColor(marker.type), fillOpacity: 0.85 }}
                >
                    <Popup>
                        <div className="min-w-[180px]">
                            <span className="text-[11px] font-bold uppercase tracking-wide text-gray-400">
                                {SECTOR_LABELS[marker.sector] ?? marker.sector} · {MARKER_TYPE_LABELS[marker.type] ?? marker.type}
                            </span>
                            <p className="text-[13px] font-extrabold text-gray-900 mt-0.5">{marker.name}</p>
                            <p className="text-[11px] text-gray-500 mt-0.5">
                                {[marker.kecamatan, marker.kelurahan].filter(Boolean).join(' · ') || '—'}
                            </p>
                            <p className="text-[11px] text-gray-400 mt-1">Status: {marker.status ?? '—'}</p>
                        </div>
                    </Popup>
                </CircleMarker>
            ))}
        </MapContainer>
    );
}
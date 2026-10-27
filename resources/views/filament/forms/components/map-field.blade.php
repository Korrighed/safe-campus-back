{{-- Leaflet CSS/JS charges via FilamentAsset (AdminPanelProvider) : ce champ peut apparaitre
    uniquement apres un premier rendu Livewire (ex. toggle "tout le territoire" desactive),
    hors d'un chargement de page complet ou un <script> injecte ici ne serait pas execute. --}}
{{-- Nouvelle-Caledonie par defaut tant qu'aucune coordonnee n'est renseignee. --}}
<div
    wire:ignore
    x-data="{
        lat: $wire.entangle('data.latitude', true),
        lng: $wire.entangle('data.longitude', true),
        map: null,
        marker: null,
        isDragging: false,
        skipNextViewReset: false,
        initMap() {
            const hasCoords = this.lat && this.lng;
            this.map = L.map(this.$refs.mapContainer).setView(
                hasCoords ? [this.lat, this.lng] : [-21.5, 165.5],
                hasCoords ? 15 : 6,
            );

            // Plan OpenStreetMap (detaille, couverture fiable partout y compris NC) et
            // Satellite Esri (legacy, sans cle API ni inscription) pour se situer visuellement.
            const planLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(this.map);

            const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                attribution: 'Sources : Esri, Maxar, Earthstar Geographics, and the GIS User Community',
                maxZoom: 19,
            });

            L.control.layers({
                'Plan': planLayer,
                'Satellite': satelliteLayer,
            }).addTo(this.map);

            if (hasCoords) {
                this.placeMarker([this.lat, this.lng]);
            }

            this.$watch('lat', () => this.isDragging || this.syncMarker());
            this.$watch('lng', () => this.isDragging || this.syncMarker());

            // Le conteneur vient d'etre (re)insere par Livewire (ex. reapparition apres
            // desactivation du toggle territoire) : Leaflet peut calculer une taille
            // erronee avant que le navigateur n'ait termine sa mise en page.
            requestAnimationFrame(() => this.map.invalidateSize());
        },
        placeMarker(position) {
            this.marker = L.marker(position, { draggable: true }).addTo(this.map);
            this.marker.on('dragstart', () => { this.isDragging = true; });
            this.marker.on('dragend', () => {
                const position = this.marker.getLatLng();
                this.isDragging = false;
                // Le pin est deja au bon endroit et le zoom courant choisi par
                // l'utilisateur ne doit pas changer : seul le geocodage doit
                // recentrer/re-zoomer la carte (nouvelle adresse, hors vue actuelle).
                this.skipNextViewReset = true;
                this.lat = position.lat;
                this.lng = position.lng;
                this.$nextTick(() => { this.skipNextViewReset = false; });
            });
        },
        syncMarker() {
            if (!this.lat || !this.lng) {
                if (this.marker) {
                    this.map.removeLayer(this.marker);
                    this.marker = null;
                }
                return;
            }

            const position = [this.lat, this.lng];

            if (this.marker) {
                this.marker.setLatLng(position);
            } else {
                this.placeMarker(position);
            }

            if (this.skipNextViewReset) {
                return;
            }

            this.map.setView(position, 15);
        },
    }"
    x-init="$nextTick(() => initMap())"
>
    <div x-ref="mapContainer" style="height: 320px; border-radius: 0.5rem;"></div>
</div>

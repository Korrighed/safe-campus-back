{{-- Filigrane prototype, identique au front (Safe-Campus-front,
     app/assets/css/components/watermark.css) — a retirer (cette vue + son
     renderHook dans AdminPanelProvider) apres publication officielle. --}}
<style>
    .fi-watermark {
        position: fixed;
        inset: 0;
        z-index: 9999;
        pointer-events: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='320' height='160'%3E%3Ctext x='160' y='80' text-anchor='middle' transform='rotate(-25 160 80)' font-family='sans-serif' font-size='20' fill='rgba(13,13,13,0.08)'%3EPROTOTYPE%3C/text%3E%3C/svg%3E");
    }
</style>
<div class="fi-watermark"></div>

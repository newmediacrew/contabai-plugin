<?php

?>
<script>
window.contabaiCardHelpers = window.contabaiCardHelpers || function (config) {
    return {
        propertyTypes: config.propertyTypes || {},
        countries: config.countries || {},
        detailBase: config.detailBase || '',

        slides: function (listing) {
            return (listing.photos || []).slice(0, 4);
        },
        loopSlides: function (listing) {
            let slides = this.slides(listing);
            return slides.length > 1 ? slides.concat([slides[0]]) : slides;
        },
        typeLabel: function (listing) {
            return this.propertyTypes[listing.property_type] || listing.property_type;
        },
        countryName: function (listing) {
            if (listing.country_name) return listing.country_name;
            let countryData = this.countries[listing.country];
            return (countryData && countryData.name) || listing.country;
        },
        cityName: function (listing) {
            if (listing.city_name) return listing.city_name;
            let countryData = this.countries[listing.country];
            let city = countryData && countryData.cities && countryData.cities[listing.city];
            return (city && city.name) || listing.city;
        },
        areaName: function (listing) {
            if (listing.area_name) return listing.area_name;
            let countryData = this.countries[listing.country];
            let city = countryData && countryData.cities && countryData.cities[listing.city];
            let areas = city && city.areas;
            return (areas && areas[listing.area]) || listing.area || '';
        },
        locationLabel: function (listing) {
            return [this.countryName(listing), this.cityName(listing), this.areaName(listing)]
                .filter(Boolean).join(' · ');
        },
        detailUrl: function (listing) {
            return this.detailBase + this.slugify(listing.country_name || listing.country).replace(/_/g, '-') + '/' + this.slugify(listing.city_name || listing.city).replace(/_/g, '-') + '/' + this.slugify(listing.title) + '-' + listing.id;
        },
        slugify: function (text) {
            return (text || '').toString().toLowerCase()
                .normalize('NFD').replace(/[̀-ͯ]/g, '')
                .replace(/&.+?;/g, '')
                .replace(/[\/.]/g, '-')
                .replace(/[^a-z0-9 _-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .replace(/(^-+|-+$)/g, '');
        },
        priceLabel: function (listing) {
            return (listing.currency || 'EUR') + ' ' + ((listing.from_price_cents || 0) / 100).toFixed(2);
        },
        reviewCount: function (listing) {
            return (listing.reviews && listing.reviews.count) || 0;
        },
        ratingLabel: function (listing) {
            return Number((listing.reviews && listing.reviews.average) || 0).toFixed(1);
        },
        reviewsLabel: function (listing) {
            let count = this.reviewCount(listing);
            if (count === 0) {
                return '<?php echo esc_js(__('No reviews yet', 'contabai')); ?>';
            }
            let label = count === 1
                ? '<?php echo esc_js(_n('%d review', '%d reviews', 1, 'contabai')); ?>'
                : '<?php echo esc_js(_n('%d review', '%d reviews', 2, 'contabai')); ?>';
            return '· ' + label.replace('%d', count);
        }
    };
};
</script>

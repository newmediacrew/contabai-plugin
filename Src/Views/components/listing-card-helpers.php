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
        nextSlide: function (listing, active) {
            let count = this.slides(listing).length;
            return count ? (active + 1) % count : 0;
        },
        prevSlide: function (listing, active) {
            let count = this.slides(listing).length;
            return count ? (active - 1 + count) % count : 0;
        },
        swipe: function (listing, active, deltaX) {
            if (Math.abs(deltaX) < 40) return active;
            return deltaX < 0 ? this.nextSlide(listing, active) : this.prevSlide(listing, active);
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
            let amount = (listing.from_price_cents || 0) / 100;
            let code = listing.currency || 'EUR';
            try {
                return new Intl.NumberFormat(undefined, { style: 'currency', currency: code, currencyDisplay: 'code' }).format(amount);
            } catch (error) {
                return code + ' ' + amount.toFixed(2);
            }
        }
    };
};
</script>

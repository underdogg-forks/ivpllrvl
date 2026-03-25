import safelist from './tailwind.safelist.json';

export default {
    content: ['./modules/**/resources/views/**/*.php', './resources/assets/**/*.{scss,css,js}'],
    safelist,
};

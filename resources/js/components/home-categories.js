/*
 * City home: filters the category grid instantly while the visitor types
 * (no server round trip) and switches between category groups.
 *
 * index: [{ id, group, text }] where text holds the names in every language,
 * the group name and search synonyms, already lower-cased by the server.
 */
const normalize = (value) => String(value ?? '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[̀-ͯ]/g, '')  // accents: "é" matches "e"
    .replace(/[ً-ْ]/g, '')  // Arabic short vowels
    .trim();

export default (index) => ({
    term: '',
    group: 'all',
    items: index.map((item) => ({ ...item, text: normalize(item.text) })),

    get words() {
        return normalize(this.term).split(/\s+/).filter(Boolean);
    },

    matches(item) {
        return (this.group === 'all' || this.group === item.group)
            && this.words.every((word) => item.text.includes(word));
    },

    visible(id) {
        const item = this.items.find((entry) => entry.id === id);

        return item ? this.matches(item) : false;
    },

    groupVisible(groupId) {
        return this.items.some((item) => item.group === groupId && this.matches(item));
    },

    get visibleCount() {
        return this.items.filter((item) => this.matches(item)).length;
    },

    get filtering() {
        return this.words.length > 0 || this.group !== 'all';
    },

    choose(groupId) {
        this.group = groupId;
        this.$nextTick(() => this.$refs.grid?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    },
});

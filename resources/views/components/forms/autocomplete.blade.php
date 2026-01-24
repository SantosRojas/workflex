@props([
    'label',
    'name',
    'items' => [],
    'itemText' => 'name',
    'itemValue' => 'id',
    'placeholder' => 'Buscar...',
    'required' => false,
    'selected' => null
])

<div x-data="{
    open: false,
    search: '',
    selectedId: @js($selected),
    selectedItem: null,
    items: @js($items),
    itemText: '{{ $itemText }}',
    itemValue: '{{ $itemValue }}',
    
    get filteredItems() {
        if (this.search === '') {
            return this.items;
        }
        const lowerSearch = this.search.toLowerCase();
        return this.items.filter(item => {
            // Concatenate name and last_name if itemText is 'name' for better search in this context
            // Or just check the configured property. 
            // For this specific app, users have name + last_name. 
            // I'll assume usage will pass a computed 'full_name' or I check multiple fields.
            // For simplicity, let's keep strict to itemText OR check if it matches logical fields.
            
            const text = item[this.itemText] ? item[this.itemText].toString().toLowerCase() : '';
            // Also check for 'last_name' if it exists and we are searching by name
            const lastName = item['last_name'] ? item['last_name'].toString().toLowerCase() : '';
            
            return text.includes(lowerSearch) || lastName.includes(lowerSearch);
        });
    },
    
    init() {
        if (this.selectedId) {
             this.selectedItem = this.items.find(i => i[this.itemValue] == this.selectedId);
             if (this.selectedItem) {
                 this.search = this.selectedItem[this.itemText] + (this.selectedItem['last_name'] ? ' ' + this.selectedItem['last_name'] : '');
             }
        }
        
        // Watch search to clear selection if modified manually (optional, but good for UX)
        this.$watch('search', (value) => {
            if (!this.open && this.selectedItem) {
                 const currentName = this.selectedItem[this.itemText] + (this.selectedItem['last_name'] ? ' ' + this.selectedItem['last_name'] : '');
                 if (value !== currentName) {
                     this.open = true; // Re-open if user types
                 }
            }
        });
    },

    selectItem(item) {
        this.selectedItem = item;
        this.selectedId = item[this.itemValue];
        this.search = item[this.itemText] + (item['last_name'] ? ' ' + item['last_name'] : '');
        this.open = false;
        this.$dispatch('item-selected', item);
    },
    
    toggle() {
        if (this.open) {
            this.open = false;
        } else {
            this.open = true;
            this.search = ''; // Optional: clear search on open? No, keep it.
        }
    },
    
    clear() {
        this.selectedItem = null;
        this.selectedId = null;
        this.search = '';
        this.$dispatch('item-selected', null);
        this.open = false; // Optional: close on clear
    }
}" class="relative w-full" @click.outside="open = false">

    <label for="{{ $name }}" class="block font-medium text-sm text-gray-700 dark:text-gray-300">
        {{ $label }}
    </label>

    <div class="relative mt-1">
        <div class="relative">
            <input 
                type="text" 
                x-model="search"
                @focus="open = true"
                @keydown.escape="open = false"
                placeholder="{{ $placeholder }}"
                class="block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm pr-10"
                autocomplete="off"
            />
            
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                <!-- Default Icon (Selector) -->
                <button type="button" x-show="!search" @click="toggle()" class="text-gray-400 focus:outline-none cursor-default" tabindex="-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                    </svg>
                </button>
                
                <!-- Clear Button (shown when has text) -->
                <button type="button" x-show="search" @click="clear()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 focus:outline-none transition-colors" tabindex="-1">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
        
        <input type="hidden" name="{{ $name }}" x-model="selectedId" {{ $required ? 'required' : '' }}>

        <div x-show="open" 
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-75"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute z-50 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-md shadow-lg max-h-60 overflow-y-auto"
             style="display: none;">
            
            <ul x-show="filteredItems.length > 0">
                <template x-for="item in filteredItems" :key="item[itemValue]">
                    <li @click="!item.disabled && selectItem(item)"
                        :class="{'opacity-50 cursor-not-allowed': item.disabled, 'cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700': !item.disabled}"
                        class="px-4 py-2 text-gray-900 dark:text-gray-300 flex justify-between items-center group">
                        <div>
                            <span class="font-medium" x-text="item[itemText]"></span>
                            <span x-show="item.last_name" x-text="' ' + item.last_name"></span>
                        </div>
                        <span x-show="item.work_area" class="text-xs text-gray-500 dark:text-gray-400" x-text="item.work_area"></span>
                    </li>
                </template>
            </ul>
            
            <div x-show="filteredItems.length === 0" class="p-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                No se encontraron resultados
            </div>
        </div>
    </div>
</div>

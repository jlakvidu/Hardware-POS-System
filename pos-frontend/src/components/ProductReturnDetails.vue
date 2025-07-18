<script setup>
import { ref, onMounted, computed } from 'vue'
import { connection } from '@/api/axios'
import {
  Package,
  Search,
  Filter,
  ChevronDown,
  ChevronLeft,
  ChevronRight,
  Calendar,
  Clock,
  User,
  ArrowUpDown,
  RotateCcw,
  X,
  Eye,
  AlertCircle,
  Download
} from 'lucide-vue-next'
import Swal from 'sweetalert2'
import html2pdf from 'html2pdf.js'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import SidebarCashier from './Sidebar-cashier.vue'

// State variables
const productReturns = ref([])
const isLoading = ref(false)
const searchQuery = ref('')
const selectedDateRange = ref('all')
const currentPage = ref(1)
const itemsPerPage = ref(10)
const sortField = ref('date')
const sortDirection = ref('desc')
const isFilterPanelOpen = ref(false)
const isExportModalOpen = ref(false)
const selectedReturns = ref([])
const isDetailModalOpen = ref(false)
const currentReturnDetail = ref(null)
const isSidebarVisible = ref(false)
const isAdmin = ref(false)

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
}

const closeSidebar = () => {
  isSidebarVisible.value = false
}

const showSidebar = () => {
  isSidebarVisible.value = true
}

// Date range options
const customStartDate = ref('')
const customEndDate = ref('')

// Add computed property for custom date error
const customDateError = computed(() => {
  if (
    selectedDateRange.value === 'custom' &&
    customStartDate.value &&
    customEndDate.value
  ) {
    const start = new Date(customStartDate.value)
    const end = new Date(customEndDate.value)
    if (start > end) {
      return 'Start date cannot be after end date.'
    }
  }
  return ''
})

const dateRanges = [
  { id: 'all', label: 'All Time' },
  { id: 'today', label: 'Today' },
  { id: 'yesterday', label: 'Yesterday' },
  { id: 'week', label: 'This Week' },
  { id: 'month', label: 'This Month' },
  { id: 'custom', label: 'Custom Range' }
]

// Fetch product returns from API
const fetchProductReturns = async () => {
  isLoading.value = true
  try {
    const response = await connection.get('/product-returns')
    productReturns.value = response.data
  } catch (error) {
    console.error('Error fetching product returns:', error)
    Swal.fire({
      title: 'Error',
      text: 'Failed to fetch product returns',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
  }
}

// Call fetchProductReturns when component mounts
onMounted(() => {
  isAdmin.value = localStorage.getItem('isAdmin') === 'true'
  fetchProductReturns()
})

// Filter product returns based on search query and date range
const filteredProductReturns = computed(() => {
  let result = [...productReturns.value]

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(item =>
      item.id.toString().toLowerCase().includes(query) ||
      (item.reason && item.reason.toLowerCase().includes(query)) ||
      (item.returned_by && item.returned_by.toLowerCase().includes(query)) ||
      (item.product && item.product.name && item.product.name.toLowerCase().includes(query))
    )
  }

  // Apply date range filter
  if (selectedDateRange.value !== 'all') {
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const yesterday = new Date(today)
    yesterday.setDate(yesterday.getDate() - 1)
    const startOfWeek = new Date(today)
    startOfWeek.setDate(today.getDate() - today.getDay())
    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)

    result = result.filter(item => {
      const itemDate = new Date(item.return_date || item.created_at)
      switch (selectedDateRange.value) {
        case 'today':
          return itemDate >= today
        case 'yesterday':
          return itemDate >= yesterday && itemDate < today
        case 'week':
          return itemDate >= startOfWeek
        case 'month':
          return itemDate >= startOfMonth
        case 'custom':
          const start = customStartDate.value ? new Date(customStartDate.value) : null
          const end = customEndDate.value ? new Date(customEndDate.value) : null
          
          if (start && end) {
            // Set end date to end of day
            end.setHours(23, 59, 59, 999)
            return itemDate >= start && itemDate <= end
          }
          return true
        default:
          return true
      }
    })
  }

  // Apply sorting
  result = result.sort((a, b) => {
    let valueA, valueB
    switch (sortField.value) {
      case 'id':
        return sortDirection.value === 'asc' 
          ? parseInt(a.id) - parseInt(b.id)
          : parseInt(b.id) - parseInt(a.id)
      case 'date':
        valueA = new Date(a.return_date || a.created_at)
        valueB = new Date(b.return_date || b.created_at)
        break
      case 'quantity':
        valueA = a.quantity
        valueB = b.quantity
        break
      case 'product':
        valueA = a.product?.name || ''
        valueB = b.product?.name || ''
        break
      default:
        valueA = a[sortField.value]
        valueB = b[sortField.value]
    }

    if (sortDirection.value === 'asc') {
      return valueA > valueB ? 1 : -1
    } else {
      return valueA < valueB ? 1 : -1
    }
  })

  return result
})

// Paginate the filtered product returns
const paginatedProductReturns = computed(() => {
  const startIndex = (currentPage.value - 1) * itemsPerPage.value
  return filteredProductReturns.value.slice(startIndex, startIndex + itemsPerPage.value)
})

// Calculate total pages
const totalPages = computed(() => {
  return Math.ceil(filteredProductReturns.value.length / itemsPerPage.value)
})

// Toggle sort direction
const toggleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

// Toggle filter panel
const toggleFilterPanel = () => {
  isFilterPanelOpen.value = !isFilterPanelOpen.value
}

// Reset custom dates
const resetCustomDates = () => {
  customStartDate.value = ''
  customEndDate.value = ''
}

// Reset filters
const resetFilters = () => {
  searchQuery.value = ''
  selectedDateRange.value = 'all'
  resetCustomDates()
  currentPage.value = 1
}

// Toggle return selection
const toggleReturnSelection = (returnId) => {
  const index = selectedReturns.value.indexOf(returnId)
  if (index === -1) {
    selectedReturns.value.push(returnId)
  } else {
    selectedReturns.value.splice(index, 1)
  }
}

// Select all returns on current page
const selectAllReturns = () => {
  if (selectedReturns.value.length === paginatedProductReturns.value.length) {
    selectedReturns.value = []
  } else {
    selectedReturns.value = paginatedProductReturns.value.map(item => item.id)
  }
}

// Open export modal
const openExportModal = () => {
  if (selectedReturns.value.length > 0) {
    isExportModalOpen.value = true
  } else {
    Swal.fire({
      title: 'No Items Selected',
      text: 'Please select at least one product return to export',
      icon: 'warning',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  }
}

// Export selected product returns
const exportProductReturns = async () => {
  isLoading.value = true
  try {
    const selectedReturnData = productReturns.value.filter(item =>
      selectedReturns.value.includes(item.id)
    )

    // Create PDF content
    const content = `
      <div style="font-family: Arial, sans-serif; padding: 20px;">
        <h1 style="color: #1a2234; text-align: center;">Product Returns Report</h1>
        <p style="text-align: center; color: #666;">Generated on ${new Date().toLocaleDateString()}</p>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
          <thead>
            <tr style="background-color: #1a2234; color: white;">
              <th style="padding: 10px; border: 1px solid #ddd;">Return ID</th>
              <th style="padding: 10px; border: 1px solid #ddd;">Product Name</th>
              <th style="padding: 10px; border: 1px solid #ddd;">Quantity</th>
              <th style="padding: 10px; border: 1px solid #ddd;">Reason</th>
              <th style="padding: 10px; border: 1px solid #ddd;">Returned By</th>
              <th style="padding: 10px; border: 1px solid #ddd;">Return Date</th>
            </tr>
          </thead>
          <tbody>
            ${selectedReturnData.map(item => `
              <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 10px; border: 1px solid #ddd;">${item.id}</td>
                <td style="padding: 10px; border: 1px solid #ddd;">${item.product?.name || 'N/A'}</td>
                <td style="padding: 10px; border: 1px solid #ddd;">${item.quantity}</td>
                <td style="padding: 10px; border: 1px solid #ddd;">${item.reason}</td>
                <td style="padding: 10px; border: 1px solid #ddd;">${item.returned_by}</td>
                <td style="padding: 10px; border: 1px solid #ddd;">${formatDate(item.return_date)}</td>
              </tr>
            `).join('')}
          </tbody>
        </table>
      </div>
    `

    // PDF options
    const options = {
      margin: 1,
      filename: `product_returns_export_${new Date().toISOString().split('T')[0]}.pdf`,
      image: { type: 'jpeg', quality: 0.98 },
      html2canvas: { scale: 2 },
      jsPDF: { unit: 'in', format: 'letter', orientation: 'landscape' }
    }

    // Generate PDF
    await html2pdf().from(content).set(options).save()

    isExportModalOpen.value = false
    Swal.fire({
      title: 'Export Successful',
      text: `${selectedReturns.value.length} product returns exported as PDF`,
      icon: 'success',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } catch (error) {
    console.error('Error exporting product returns:', error)
    Swal.fire({
      title: 'Export Failed',
      text: 'Failed to export product returns. Please try again.',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
    selectedReturns.value = []
  }
}

// View return details
const viewReturnDetails = (item) => {
  currentReturnDetail.value = item
  isDetailModalOpen.value = true
}

// Format date
const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  })
}

// Format time
const formatTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-[#0f172a] to-[#1e293b] text-white font-sans">
    <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger" @mouseenter="showSidebar"></div>
    
    <!-- Conditionally render sidebar based on admin status -->
    <SidebarCashier 
      v-if="!isAdmin"
      :isVisible="isSidebarVisible" 
      @closeSidebar="closeSidebar" 
    />
    <Sidebar
      v-else
      :isVisible="isSidebarVisible"
      @closeSidebar="closeSidebar"
    />
    
    <Header @toggleSidebar="toggleSidebar" />

    <div class="w-full p-4 lg:p-8 pt-28 lg:pt-28">
      <!-- Header Section -->
      <header class="bg-[#1a2234]/95 border-b border-[#334155] sticky top-0 z-10 backdrop-blur-sm shadow-lg">
        <div class="max-w-[1920px] mx-auto px-6 py-5">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
              <h1 class="text-2xl font-bold bg-gradient-to-r from-[#4dabf7] to-[#3b82f6] bg-clip-text text-transparent flex items-center gap-2">
                <RotateCcw class="w-6 h-6 text-[#4dabf7]" />
                Product Returns Management
              </h1>
              <p class="text-gray-400 text-sm mt-1">View and manage product return records efficiently</p>
            </div>
            
            <div class="flex items-center gap-3 w-full md:w-auto">
              <div class="relative flex-1 md:w-64">
                <input 
                  type="text" 
                  v-model="searchQuery" 
                  placeholder="Search returns..."
                  class="bg-[#1e293b] border border-[#334155] rounded-lg pl-10 pr-4 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white placeholder-gray-500" 
                />
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              </div>
              
              <button 
                @click="toggleFilterPanel"
                class="p-2.5 bg-[#1e293b] border border-[#334155] rounded-lg hover:bg-[#263244] transition-colors flex items-center justify-center"
                :class="{ 'bg-[#3b82f6]/20 border-[#3b82f6]/50': isFilterPanelOpen }"
                aria-label="Toggle filter panel"
              >
                <Filter class="w-5 h-5" :class="{ 'text-[#3b82f6]': isFilterPanelOpen }" />
              </button>
              
              <button 
                @click="openExportModal" 
                :disabled="selectedReturns.length === 0"
                :class="[
                  'p-2.5 rounded-lg transition-colors flex items-center justify-center gap-2 text-sm font-medium',
                  selectedReturns.length === 0
                    ? 'bg-[#1e293b]/50 border border-[#334155]/50 text-gray-500 cursor-not-allowed'
                    : 'bg-[#1e293b] border border-[#334155] hover:bg-[#263244] text-white'
                ]"
                aria-label="Export selected returns"
              >
                <Download class="w-5 h-5" />
                <span class="hidden sm:inline">Export</span>
              </button>
            </div>
          </div>
        </div>
      </header>

      <!-- Filter Panel -->
      <div 
        v-if="isFilterPanelOpen"
        class="bg-[#1a2234]/95 border-b border-[#334155] py-5 animate-slide-down shadow-lg backdrop-blur-sm"
      >
        <div class="max-w-[1920px] mx-auto px-4">
          <div class="flex flex-col md:flex-row gap-4 md:items-end">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1">
              <div>
                <label class="block text-sm text-gray-400 mb-1.5 font-medium" for="date-filter">Date Range</label>
                <div class="relative">
                  <select 
                    id="date-filter" 
                    v-model="selectedDateRange"
                    class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg pl-4 pr-10 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white"
                  >
                    <option v-for="range in dateRanges" :key="range.id" :value="range.id">
                      {{ range.label }}
                    </option>
                  </select>
                  <Calendar class="absolute right-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400 pointer-events-none" />
                </div>
              </div>
              
              <!-- Custom date range inputs -->
              <div v-if="selectedDateRange === 'custom'" class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm text-gray-400 mb-1.5 font-medium" for="start-date">Start Date</label>
                  <input
                    type="date"
                    id="start-date"
                    v-model="customStartDate"
                    class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg px-4 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white"
                  />
                </div>
                <div>
                  <label class="block text-sm text-gray-400 mb-1.5 font-medium" for="end-date">End Date</label>
                  <input
                    type="date"
                    id="end-date"
                    v-model="customEndDate"
                    :min="customStartDate"
                    class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg px-4 py-2.5 w-full focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white"
                  />
                </div>
                <!-- Error message under date inputs -->
                <div class="col-span-2" v-if="customDateError">
                  <p class="text-red-400 text-xs mt-1">{{ customDateError }}</p>
                </div>
              </div>
            </div>
            
            <div class="flex gap-3">
              <button 
                @click="resetFilters"
                class="py-2.5 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors text-sm font-medium text-white"
              >
                Reset
              </button>
              <button 
                @click="toggleFilterPanel"
                :disabled="!!customDateError"
                class="py-2.5 px-4 bg-[#3b82f6] hover:bg-[#2563eb] rounded-lg transition-colors text-sm font-medium text-white disabled:opacity-60 disabled:cursor-not-allowed"
              >
                Apply Filters
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="max-w-[1920px] mx-auto px-6 py-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-[#1a2234]/80 backdrop-blur-sm border border-[#334155] rounded-xl p-6 flex items-center shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
            <div class="p-3 bg-gradient-to-br from-purple-500/20 to-purple-600/20 text-purple-400 rounded-lg mr-4 shadow-inner">
              <RotateCcw class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Total Returns</h3>
              <div class="text-2xl font-bold bg-gradient-to-r from-gray-100 to-gray-300 bg-clip-text text-transparent mt-1">
                {{ productReturns.length }}
              </div>
            </div>
          </div>

          <div class="bg-[#1a2234] border border-[#334155] rounded-lg p-5 flex items-center shadow-sm hover:shadow-md transition-shadow">
            <div class="p-3 bg-blue-500/20 text-blue-400 rounded-lg mr-4">
              <Package class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Total Products Returned</h3>
              <div class="text-2xl font-bold text-white mt-1">
                {{ productReturns.reduce((sum, item) => sum + item.quantity, 0) }}
              </div>
            </div>
          </div>

          <div class="bg-[#1a2234] border border-[#334155] rounded-lg p-5 flex items-center shadow-sm hover:shadow-md transition-shadow">
            <div class="p-3 bg-orange-500/20 text-orange-400 rounded-lg mr-4">
              <Calendar class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Today's Returns</h3>
              <div class="text-2xl font-bold text-white mt-1">
                {{ productReturns.filter(item => {
                  const today = new Date();
                  today.setHours(0, 0, 0, 0);
                  return new Date(item.return_date || item.created_at) >= today;
                }).length }}
              </div>
            </div>
          </div>

          <div class="bg-[#1a2234] border border-[#334155] rounded-lg p-5 flex items-center shadow-sm hover:shadow-md transition-shadow">
            <div class="p-3 bg-emerald-500/20 text-emerald-400 rounded-lg mr-4">
              <Clock class="w-6 h-6" />
            </div>
            <div>
              <h3 class="text-sm text-gray-400 font-medium">Last Return</h3>
              <div class="text-2xl font-bold text-white mt-1">
                {{ productReturns.length > 0 ? formatDate(productReturns[0].return_date) : 'N/A' }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Table -->
      <div class="max-w-[1920px] mx-auto px-6 pb-8">
        <div class="bg-[#1a2234]/80 backdrop-blur-sm border border-[#334155] rounded-xl overflow-hidden relative shadow-xl">
          <div class="p-5 border-b border-[#334155] bg-[#1e293b]/50">
            <div class="flex justify-between items-center">
              <div class="flex items-center gap-2">
                <div class="p-2 bg-purple-500/20 text-purple-400 rounded-lg">
                  <RotateCcw class="w-5 h-5" />
                </div>
                <h2 class="font-bold text-gray-200 text-lg">Product Returns</h2>
                <div class="ml-2 text-sm text-gray-400">
                  {{ filteredProductReturns.length }} items found
                </div>
              </div>
              
              <div class="flex items-center gap-3">
                <div class="relative">
                  <select 
                    v-model="itemsPerPage"
                    class="appearance-none bg-[#1e293b] border border-[#334155] rounded-lg pl-4 pr-10 py-2 text-sm w-24 focus:outline-none focus:ring-1 focus:ring-[#3b82f6]/30 text-white"
                    aria-label="Rows per page"
                  >
                    <option value="5">5 rows</option>
                    <option value="10">10 rows</option>
                    <option value="20">20 rows</option>
                    <option value="50">50 rows</option>
                  </select>
                  <ChevronDown class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                </div>
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full min-w-[800px]">
              <thead>
                <tr class="bg-gradient-to-r from-[#1e293b]/70 to-[#1e293b]/50 text-gray-400 text-sm">
                  <th class="py-3 px-4 text-left">
                    <div class="flex items-center">
                      <input 
                        type="checkbox" 
                        @change="selectAllReturns"
                        :checked="selectedReturns.length === paginatedProductReturns.length && paginatedProductReturns.length > 0"
                        class="rounded border-gray-600 text-[#3b82f6] focus:ring-[#3b82f6]/30 bg-gray-700 w-4 h-4"
                        aria-label="Select all returns" 
                      />
                    </div>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button 
                      @click="toggleSort('id')" 
                      class="flex items-center gap-1 hover:text-white transition-colors"
                      aria-label="Sort by Return ID"
                    >
                      Return ID
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'id' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button 
                      @click="toggleSort('product')"
                      class="flex items-center gap-1 hover:text-white transition-colors"
                      aria-label="Sort by Product Name"
                    >
                      Product Name
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'product' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">
                    <button 
                      @click="toggleSort('quantity')"
                      class="flex items-center gap-1 hover:text-white transition-colors"
                      aria-label="Sort by Quantity"
                    >
                      Quantity
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'quantity' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-left">Reason</th>
                  <th class="py-3 px-4 text-left">Returned By</th>
                  <th class="py-3 px-4 text-left">
                    <button 
                      @click="toggleSort('date')"
                      class="flex items-center gap-1 hover:text-white transition-colors"
                      aria-label="Sort by Date"
                    >
                      Return Date
                      <ArrowUpDown class="w-4 h-4" :class="{ 'text-[#3b82f6]': sortField === 'date' }" />
                    </button>
                  </th>
                  <th class="py-3 px-4 text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="isLoading">
                  <td colspan="8" class="py-12 text-center text-gray-500">
                    Loading product returns...
                  </td>
                </tr>
                <tr v-else-if="paginatedProductReturns.length === 0">
                  <td colspan="8" class="py-12 text-center text-gray-500">
                    <RotateCcw class="w-16 h-16 mx-auto mb-4 opacity-30" />
                    <p class="text-lg font-medium">No product returns found</p>
                    <p class="text-sm mt-1">Try changing your search or filter criteria</p>
                  </td>
                </tr>
                <tr 
                  v-for="item in paginatedProductReturns" 
                  :key="item.id"
                  class="border-t border-[#334155]/30 hover:bg-[#1e293b]/50 transition-colors duration-200"
                >
                  <td class="py-4 px-4">
                    <input 
                      type="checkbox" 
                      :checked="selectedReturns.includes(item.id)"
                      @change="toggleReturnSelection(item.id)"
                      class="rounded border-gray-600 text-[#3b82f6] focus:ring-[#3b82f6]/30 bg-gray-700 w-4 h-4"
                      :aria-label="`Select return ${item.id}`" 
                    />
                  </td>
                  <td class="py-4 px-4 font-medium text-[#4dabf7]">{{ item.id }}</td>
                  <td class="py-4 px-4">
                    <div class="flex items-center gap-2">
                      <div class="w-8 h-8 rounded-full flex items-center justify-center bg-[#3b82f6]/20 text-[#3b82f6]">
                        <Package class="w-4 h-4" />
                      </div>
                      <span>{{ item.product?.name || 'N/A' }}</span>
                    </div>
                  </td>
                  <td class="py-4 px-4 font-medium">{{ item.quantity }}</td>
                  <td class="py-4 px-4">
                    <div class="max-w-[200px] truncate" :title="item.reason">
                      {{ item.reason }}
                    </div>
                  </td>
                  <td class="py-4 px-4">{{ item.returned_by }}</td>
                  <td class="py-4 px-4">
                    <div class="flex flex-col">
                      <span>{{ formatDate(item.return_date) }}</span>
                      <span class="text-xs text-gray-400 mt-1">{{ formatTime(item.return_date) }}</span>
                    </div>
                  </td>
                  <td class="py-4 px-4">
                    <div class="flex items-center justify-center gap-2">
                      <button 
                        @click="viewReturnDetails(item)"
                        class="p-1.5 hover:bg-[#334155] rounded-lg transition-colors group" 
                        title="View Details"
                        aria-label="View return details"
                      >
                        <Eye class="w-4 h-4 text-blue-400 group-hover:text-blue-300" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div class="max-w-[1920px] mx-auto px-6 pb-8 mt-4">
        <div class="bg-[#1a2234]/80 backdrop-blur-sm border border-[#334155] rounded-xl overflow-hidden p-4 flex justify-between items-center">
          <div class="text-sm text-gray-400">
            Showing {{ (currentPage - 1) * itemsPerPage + 1 }} to
            {{ Math.min(currentPage * itemsPerPage, filteredProductReturns.length) }} of
            {{ filteredProductReturns.length }} product returns
          </div>
          
          <div class="flex items-center gap-2">
            <button 
              @click="currentPage--" 
              :disabled="currentPage === 1"
              class="p-2 rounded-lg bg-[#1e293b] border border-[#334155] text-gray-400 hover:bg-[#263244] transition-colors"
              aria-label="Previous page"
            >
              <ChevronLeft class="w-5 h-5" />
            </button>
            
            <div class="flex items-center">
              <button 
                v-for="page in Math.min(totalPages, 5)" 
                :key="page" 
                @click="currentPage = page"
                :class="[
                  'w-10 h-10 rounded-lg transition-colors text-sm font-medium ml-1',
                  currentPage === page
                    ? 'bg-[#3b82f6] text-white'
                    : 'bg-[#1e293b] border border-[#334155] text-gray-300 hover:bg-[#263244]'
                ]"
                :aria-label="`Page ${page}`"
                :aria-current="currentPage === page ? 'page' : null"
              >
                {{ page }}
              </button>
            </div>
            
            <button 
              @click="currentPage++" 
              :disabled="currentPage === totalPages"
              class="p-2 rounded-lg bg-[#1e293b] border border-[#334155] text-gray-400 hover:bg-[#263244] transition-colors"
              aria-label="Next page"
            >
              <ChevronRight class="w-5 h-5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Export Modal -->
      <div v-if="isExportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-fade-in">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-[6px]" @click="isExportModalOpen = false"></div>
        <div class="relative bg-gradient-to-b from-[#1a2234] to-[#161d2b] rounded-2xl shadow-2xl border border-[#334155]/50 w-full max-w-md p-8 z-10 animate-slide-up">
          <div class="absolute top-4 right-4">
            <button 
              @click="isExportModalOpen = false" 
              class="p-2 hover:bg-[#334155]/30 rounded-full transition-colors group" 
              aria-label="Close export modal"
            >
              <X class="w-5 h-5 text-gray-400 group-hover:text-white transition-colors" />
            </button>
          </div>
          
          <div class="mb-6">
            <h2 class="text-2xl font-bold bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">Export Product Returns</h2>
            <p class="text-gray-400 mt-2 text-sm">Export the selected returns to PDF format</p>
          </div>
          
          <div class="space-y-6">
            <div class="p-4 bg-[#1e293b]/50 rounded-xl border border-[#334155]/50">
              <div class="text-sm text-gray-400">Selected Items</div>
              <div class="text-2xl font-bold text-white mt-1">{{ selectedReturns.length }} items</div>
            </div>
            
            <div class="flex gap-3 pt-4">
              <button 
                @click="isExportModalOpen = false"
                class="flex-1 py-3 px-4 bg-[#1e293b] hover:bg-[#263244] border border-[#334155]/50 rounded-xl transition-all duration-300 text-sm font-medium text-gray-300"
              >
                Cancel
              </button>
              <button 
                @click="exportProductReturns"
                class="flex-1 py-3 px-4 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 rounded-xl transition-all duration-300 flex items-center justify-center gap-2 text-sm font-medium text-white shadow-lg shadow-blue-500/25"
                :disabled="isLoading"
              >
                <span v-if="isLoading" class="w-5 h-5 border-2 border-t-white/20 border-white rounded-full animate-spin"></span>
                <Download v-else class="w-5 h-5" />
                <span>Export to PDF</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Detail Modal -->
      <div v-if="isDetailModalOpen && currentReturnDetail" class="fixed inset-0 z-50 flex items-center justify-center p-4 animate-fade-in">
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="isDetailModalOpen = false"></div>
        <div class="relative bg-gradient-to-br from-[#1a2234] via-[#161d2b] to-[#1a2234] rounded-2xl shadow-2xl border border-[#334155]/50 w-full max-w-3xl p-8 z-10 animate-slide-up max-h-[90vh] overflow-auto custom-scrollbar">
          <!-- Header -->
          <div class="absolute top-6 right-6 flex gap-2">
            <button 
              @click="isDetailModalOpen = false" 
              class="p-2.5 bg-[#1e293b]/80 hover:bg-[#263244] border border-[#334155] rounded-lg transition-all duration-300 group"
              aria-label="Close details"
            >
              <X class="w-5 h-5 text-gray-400 group-hover:text-white transition-colors" />
            </button>
          </div>
          
          <div class="mb-8">
            <div class="inline-flex items-center px-3 py-1.5 bg-blue-500/10 rounded-full mb-4">
              <span class="text-blue-400 text-sm font-medium">Product Return Details</span>
            </div>
            <div class="flex items-center gap-3">
              <div class="p-3 bg-gradient-to-br from-blue-500/20 to-blue-600/10 border border-blue-500/20 rounded-xl">
                <Package class="w-7 h-7 text-blue-400" />
              </div>
              <div>
                <h2 class="text-2xl font-bold text-white">
                  Return #{{ currentReturnDetail.id }}
                </h2>
                <p class="text-gray-400 text-sm mt-1">Detailed information about this product return</p>
              </div>
            </div>
          </div>
          
          <div class="space-y-6">
            <!-- Info Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Product Info Card -->
              <div class="bg-[#1e293b]/40 backdrop-blur-sm rounded-xl border border-[#334155]/50 overflow-hidden">
                <div class="p-5 border-b border-[#334155]/50">
                  <h3 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
                    <Package class="w-4 h-4 text-blue-400" />
                    Product Information
                  </h3>
                </div>
                <div class="p-5">
                  <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl flex items-center justify-center bg-gradient-to-br from-blue-500/20 to-blue-600/10 border border-blue-500/20">
                      <Package class="w-7 h-7 text-blue-400" />
                    </div>
                    <div>
                      <div class="text-sm text-gray-400">Product Name</div>
                      <div class="text-lg font-semibold text-white mt-1">{{ currentReturnDetail.product?.name || 'N/A' }}</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Return Details Card -->
              <div class="bg-[#1e293b]/40 backdrop-blur-sm rounded-xl border border-[#334155]/50 overflow-hidden">
                <div class="p-5 border-b border-[#334155]/50">
                  <h3 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
                    <RotateCcw class="w-4 h-4 text-blue-400" />
                    Return Details
                  </h3>
                </div>
                <div class="p-5 space-y-4">
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-400">Quantity</div>
                    <div class="text-lg font-semibold text-white">{{ currentReturnDetail.quantity }}</div>
                  </div>
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-400">Return Date</div>
                    <div class="text-white">{{ formatDate(currentReturnDetail.return_date) }}</div>
                  </div>
                  <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-400">Returned By</div>
                    <div class="text-white">{{ currentReturnDetail.returned_by }}</div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Return Reason Card -->
            <div class="bg-[#1e293b]/40 backdrop-blur-sm rounded-xl border border-[#334155]/50 overflow-hidden">
              <div class="p-5 border-b border-[#334155]/50">
                <h3 class="text-sm font-semibold text-gray-300 flex items-center gap-2">
                  <AlertCircle class="w-4 h-4 text-blue-400" />
                  Return Reason
                </h3>
              </div>
              <div class="p-5">
                <p class="text-white leading-relaxed">{{ currentReturnDetail.reason }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-slide-down {
  animation: slideDown 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.animate-scale-in {
  animation: scaleIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

@keyframes slideDown {
  0% {
    opacity: 0;
    transform: translateY(-20px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scaleIn {
  0% {
    opacity: 0;
    transform: scale(0.95) translateY(-10px);
  }
  100% {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

button {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

button:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
}

button:active:not(:disabled) {
  transform: translateY(0);
  box-shadow: none;
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(75, 85, 99, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(75, 85, 99, 0.3);
  border-radius: 6px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(75, 85, 99, 0.5);
}

.animate-fade-in {
  animation: fadeIn 0.3s ease-out forwards;
}

.animate-slide-up {
  animation: slideUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes fadeIn {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}

@keyframes slideUp {
  0% {
    opacity: 0;
    transform: translateY(20px) scale(0.96);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
<script setup>
import { useRouter } from 'vue-router' 
import { ref, computed, onMounted, watch } from 'vue'
import Header from './Header.vue'
import Sidebar from './Sidebar.vue'
import SidebarCashier from './Sidebar-cashier.vue' // Add this import
import Swal from 'sweetalert2'
import {
  ShoppingCart,
  CreditCard,
  Banknote,
  Package,
  Plus,
  Minus,
  Trash2,
  ChevronDown,
  Check,
  X,
  LayoutGrid,
  ChevronLeft,
  ChevronRight,
  Search,
  User,
  FileText,
  Tag,
  Percent,
  Printer,
  Box,
  Layers
} from 'lucide-vue-next'
import {
  ShieldCheckIcon,
  WrenchScrewdriverIcon,
  BoltIcon,
  WrenchIcon,
  BuildingOffice2Icon,
  HomeModernIcon,
  QueueListIcon,
  UserPlusIcon 
} from '@heroicons/vue/24/outline'
import { connection } from '../api/axios'
import html2pdf from 'html2pdf.js'

const router = useRouter() 

const navigateToCustomers = () => {
  const isAdmin = localStorage.getItem('isAdmin') === 'true';
  Swal.fire({
    title: 'Add New Customer?',
    text: "Would you like to register a new customer?",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3B82F6',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Yes, add customer',
    cancelButtonText: 'Cancel',
    background: '#1e293b',
    color: '#ffffff'
  }).then((result) => {
    if (result.isConfirmed) {
      if (isAdmin) {
        router.push('/customers');
      } else {
        router.push('/customers-cashier');
      }
    }
  });
}

const isSidebarVisible = ref(false)
const isPaymentModalOpen = ref(false)
const isReceiptVisible = ref(false)
const selectedPaymentMethod = ref('CASH') // Change default payment method
const orderItems = ref([])
const searchQuery = ref('')
const transactionId = ref('')
const invoiceNumber = ref('')
const currentDate = ref('')
const applyDiscount = ref(false)
const customDiscountRate = ref(10)

const customers = ref([])
const loading = ref(true)

const toggleSidebar = () => {
  isSidebarVisible.value = !isSidebarVisible.value
}

const closeSidebar = () => {
  isSidebarVisible.value = false
}

const showSidebar = () => {
  isSidebarVisible.value = true
}

const currentCategoryPage = ref(0)
const categoriesPerPage = ref(4)

const updateCategoriesPerPage = () => {
  if (window.innerWidth < 640) {
    categoriesPerPage.value = 2
  } else if (window.innerWidth < 1024) {
    categoriesPerPage.value = 3
  } else if (window.innerWidth < 1280) {
    categoriesPerPage.value = 4
  } else {
    categoriesPerPage.value = 5
  }
}

onMounted(() => {
  updateCategoriesPerPage()
  window.addEventListener('resize', updateCategoriesPerPage)

  orderId.value = '#' + Math.floor(10000 + Math.random() * 90000)
  invoiceNumber.value = Math.floor(10000 + Math.random() * 90000).toString()
  transactionId.value = '00000' + Math.floor(1000 + Math.random() * 9000).toString()

  const now = new Date()
  currentDate.value = `${now.toLocaleString('default', { month: 'long' })} ${now.getDate()}, ${now.getFullYear()}`
})

const beforeUnmount = () => {
  window.removeEventListener('resize', updateCategoriesPerPage)
}

const products = ref([])
const error = ref(null)

const fetchProducts = async () => {
  try {
    loading.value = true
    const response = await connection.get('/products')
    const productsWithInventory = await Promise.all(
      response.data.data.map(async (product) => {
        const inventoryResponse = await connection.get(`/inventory/${product.inventory_id}`)
        return {
          ...product,
          price: product.price, // Adjust price after selling discount
          stock: inventoryResponse.data.quantity
        }
      })
    )
    products.value = productsWithInventory
  } catch (err) {
    error.value = 'Failed to fetch products'
    console.error('Error fetching products:', err)
  } finally {
    loading.value = false
  }
}

// Simplify filtered products to only use search
const filteredProducts = computed(() => {
  let result = products.value
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(product =>
      product.name.toLowerCase().includes(query)
    )
  }
  return result
})

watch(categoriesPerPage, () => {
  if (currentCategoryPage.value >= maxCategoryPages.value) {
    currentCategoryPage.value = Math.max(0, maxCategoryPages.value - 1)
  }
})

const addToOrder = (product) => {
  const existingItem = orderItems.value.find(item => item.id === product.id)

  if (existingItem) {
    if (existingItem.quantity >= product.stock) {
      Swal.fire({
        title: 'Stock Limit Reached',
        text: 'Cannot add more items than available in stock',
        icon: 'warning',
        background: '#1e293b',
        color: '#ffffff'
      })
      return
    }
    existingItem.quantity++
  } else {
    if (product.stock > 0) {
      orderItems.value.push({
        id: product.id,
        name: product.name,
        price: product.price,
        quantity: 1,
        initialStock: product.stock,
        productDiscount: 0,
        width: 0,
        height: 0,
        totalArea: 0,
        dimensions: ''
      })
      showToast(`Added ${product.name} to order`)
    } else {
      Swal.fire({
        title: 'Out of Stock',
        text: 'This item is currently out of stock',
        icon: 'error',
        background: '#1e293b',
        color: '#ffffff'
      })
      return
    }
  }
}

const updateDimensions = (item) => {
  if (item.width && item.height) {
    item.totalArea = item.width * item.height
    item.totalAreaMeters = (item.width * item.height * 0.00064516).toFixed(2)
    item.dimensions = `${item.width}" × ${item.height}" | ${item.totalAreaMeters} m²`
  }
}

const openDimensionsModal = (item) => {
  Swal.fire({
    title: 'Enter Banner Dimensions',
    html: `
      <div class="space-y-4">
        <div>
          <label class="block text-sm text-gray-400 mb-1">Width (inches)</label>
          <input id="width" type="number" min="1" step="0.1" value="${item.width || ''}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white" />
        </div>
        <div>
          <label class="block text-sm text-gray-400 mb-1">Height (inches)</label>
          <input id="height" type="number" min="1" step="0.1" value="${item.height || ''}" class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white" />
        </div>
        <div class="mt-4 p-3 bg-gray-800 rounded-lg">
          <div class="text-sm text-gray-400">Preview:</div>
          <div class="text-white mt-1" id="preview">Enter dimensions to see preview</div>
        </div>
      </div>
    `,
    showCancelButton: true,
    confirmButtonText: 'Update Dimensions',
    background: '#1e293b',
    color: '#ffffff',
    didOpen: () => {
      const width = document.getElementById('width');
      const height = document.getElementById('height');
      const preview = document.getElementById('preview');
      
      const updatePreview = () => {
        const w = parseFloat(width.value);
        const h = parseFloat(height.value);
        if (w && h) {
          const sqMeters = (w * h * 0.00064516).toFixed(2);
          preview.innerHTML = `
            <div class="flex flex-col gap-1">
              <div class="text-blue-400">${w}" × ${h}"</div>
              <div class="text-green-400">${sqMeters} m²</div>
            </div>
          `;
        }
      };
      
      width.addEventListener('input', updatePreview);
      height.addEventListener('input', updatePreview);
    },
    preConfirm: () => {
      const width = document.getElementById('width').value
      const height = document.getElementById('height').value
      if (!width || !height) {
        Swal.showValidationMessage('Please enter both width and height')
      }
      return { width: parseFloat(width), height: parseFloat(height) }
    }
  }).then((result) => {
    if (result.isConfirmed) {
      const { width, height } = result.value
      item.width = width
      item.height = height
      updateDimensions(item)
    }
  })
}

const updateProductDiscount = (item, value) => {
  if (value < 0) value = 0
  if (value > 100) value = 100
  item.productDiscount = value
}

const getItemTotal = (item) => {
  const priceAfterDiscount = item.price * (1 - item.productDiscount / 100)
  return priceAfterDiscount * item.quantity
}

const subtotal = computed(() => {
  return orderItems.value.reduce((total, item) => {
    return total + getItemTotal(item)
  }, 0)
})

const discount = computed(() => {
  if (!applyDiscount.value) return 0
  return subtotal.value * (customDiscountRate.value / 100)
})

const total = computed(() => subtotal.value - discount.value)

const showToast = (message) => {
  const toast = document.createElement('div')
  toast.className = 'fixed bottom-8 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in flex items-center gap-2'
  toast.innerHTML = `
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
    </svg>
    ${message}`
  document.body.appendChild(toast)

  setTimeout(() => {
    toast.classList.add('animate-fade-out')
    setTimeout(() => {
      document.body.removeChild(toast)
    }, 300)
  }, 2000)
}

const updateQuantity = (item, change) => {
  const product = products.value.find(p => p.id === item.id)
  const newQuantity = item.quantity + change

  if (newQuantity <= 0) {
    removeFromOrder(item.id)
    return
  }

  if (newQuantity > product.stock) {
    Swal.fire({
      title: 'Stock Limit Reached',
      text: `Maximum available stock is ${product.stock} units`,
      icon: 'warning',
      background: '#1e293b',
      color: '#ffffff'
    })
    item.quantity = product.stock
    return
  }

  item.quantity = newQuantity
}

const validateQuantity = (item) => {
  const product = products.value.find(p => p.id === item.id);
  if (!item.quantity || item.quantity <= 0) {
    item.quantity = 1; // Reset to a minimum valid value
  } else if (item.quantity > product.stock) {
    item.quantity = product.stock; // Cap at stock limit
    Swal.fire({
      title: 'Stock Limit Reached',
      text: `Maximum available stock is ${product.stock} units`,
      icon: 'warning',
      background: '#1e293b',
      color: '#ffffff'
    });
  }
};

const removeFromOrder = (itemId) => {
  orderItems.value = orderItems.value.filter(item => item.id !== itemId)
}

const clearOrder = () => {
  orderItems.value = []
  customerName.value = 'Walk-in Customer'
  applyDiscount.value = false
  customDiscountRate.value = 10
}

const openPaymentModal = () => {
  if (orderItems.value.length > 0) {
    isPaymentModalOpen.value = true
  }
}

const cashierId = ref(null) // Update cashierId ref and add getUserData
const orderStatus = ref(1) // 1 for completed, 0 for pending etc.
const isProcessingOrder = ref(false)  // Add this new ref

const getUserData = () => {
  const userData = localStorage.getItem('user')
  if (userData) {
    const user = JSON.parse(userData)
    cashierId.value = user.id
  }
}

const updateProductStock = (productId, quantity) => {
  const product = products.value.find(p => p.id === productId)
  if (product) {
    product.stock -= quantity
  }
}

const isAdvancePayment = ref(false)
const advanceAmount = ref(0)
const remainingBalance = computed(() => total.value - advanceAmount.value)
const paymentStatus = computed(() => {
  if (!isAdvancePayment.value) return 'FULL_PAYMENT'
  if (advanceAmount.value === 0) return 'PENDING'
  if (advanceAmount.value === total.value) return 'PAID'
  return 'PARTIALLY_PAID'
})

const formatCurrency = (amount) => {
  return new Intl.NumberFormat('en-LK', {
    style: 'currency',
    currency: 'LKR'
  }).format(amount)
}

const handleAdvancePayment = async () => {
  try {
    isProcessingOrder.value = true;

    if (!cashierId.value) {
      throw new Error('No cashier ID found. Please login again.');
    }

    const salesData = {
      time: new Date().toISOString(),
      status: 2, // 2 for partially paid
      payment_type: selectedPaymentMethod.value,
      amount: total.value,
      advance_amount: advanceAmount.value,
      remaining_balance: remainingBalance.value,
      cashier_id: cashierId.value,
      customer_id: selectedCustomer.value?.id,
      discount: applyDiscount.value ? customDiscountRate.value : 0,
      items: orderItems.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price,
        product_discount: item.productDiscount,
        width: item.width || null,
        height: item.height || null,
        totalAreaMeters: item.totalAreaMeters || null
      }))
    };

    const response = await connection.post('/sales', salesData);
    
    if (response.status === 201) {
      orderId.value = response.data.data.id;
      isPaymentModalOpen.value = false;

      Swal.fire({
        title: 'Advance Payment Recorded!',
        text: 'Do you want to print a receipt?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, print receipt',
        cancelButtonText: 'No, thanks',
        background: '#1e293b',
        color: '#ffffff'
      }).then((result) => {
        if (result.isConfirmed) {
          isReceiptVisible.value = true;
        } else {
          clearOrder();
        }
      });
    }
  } catch (error) {
    console.error('Error processing advance payment:', error);
    Swal.fire({
      title: 'Error!',
      text: 'Failed to process advance payment. Please try again.',
      icon: 'error',
      background: '#1e293b',
      color: '#ffffff'
    });
  } finally {
    isProcessingOrder.value = false;
  }
};

const completeOrder = async () => {
  if (isAdvancePayment.value) {
    await handleAdvancePayment();
    return;
  }

  try {
    isProcessingOrder.value = true;

    if (!cashierId.value) {
      throw new Error('No cashier ID found. Please login again.');
    }

    // Ensure checkDetails.amount is set to total for check payments
    if (selectedPaymentMethod.value === 'CHECK') {
      checkDetails.value.amount = total.value;
    }

    const salesData = {
      time: new Date().toISOString(),
      status: isAdvancePayment.value ? 2 : 1,
      payment_type: selectedPaymentMethod.value,
      amount: total.value,
      advance_amount: isAdvancePayment.value ? advanceAmount.value : total.value,
      remaining_balance: isAdvancePayment.value ? remainingBalance.value : 0,
      payment_status: paymentStatus.value,
      cashier_id: cashierId.value,
      customer_id: customerName.value === 'Walk-in Customer' ? null : 
        customers.value.find(c => c.name === customerName.value)?.id,
      discount: applyDiscount.value ? customDiscountRate.value : 0,
      items: orderItems.value.map(item => ({
        product_id: item.id,
        quantity: item.quantity,
        price: item.price,
        product_discount: item.productDiscount,
        width: item.width || null,
        height: item.height || null,
        totalAreaMeters: item.totalAreaMeters || null
      }))
    };

    // Add check_details if payment type is CHECK
    if (selectedPaymentMethod.value === 'CHECK') {
      salesData.check_details = { ...checkDetails.value };
    }

    const response = await connection.post('/sales', salesData);
    console.log('Order Response:', response.data);

    if (response.status === 201) {
      // Update local stock after successful order
      orderItems.value.forEach(item => {
        updateProductStock(item.id, item.quantity);
      });

      // Assign the actual order ID from the backend response
      orderId.value = response.data.data.id;

      isPaymentModalOpen.value = false;
      console.log('Order completed successfully!');

      Swal.fire({
        title: 'Order Completed!',
        text: 'Do you want to print a receipt?',
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#3B82F6',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, print receipt',
        cancelButtonText: 'No, thanks',
        background: '#1e293b',
        color: '#ffffff'
      }).then((result) => {
        if (result.isConfirmed) {
          isReceiptVisible.value = true;
        } else {
          clearOrder();
        }
      });
    }
  } catch (error) {
    console.error('Error completing order:', {
      error,
      errorMessage: error.message,
      errorResponse: error.response?.data
    });

    Swal.fire({
      title: 'Error!',
      text: 'Failed to complete the order. Please try again.',
      icon: 'error',
      background: '#1e293b',
      color: '#ffffff'
    });
  } finally {
    isProcessingOrder.value = false;
  }
};

const printReceipt = () => {
  const element = document.getElementById('receipt-content')
  const opt = {
    margin: [10, 10, 10, 10],
    filename: `order-${orderId.value}.pdf`, // Use orderId for filename
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { 
      scale: 2,
      useCORS: true,
      logging: false
    },
    jsPDF: { 
      unit: 'mm', 
      format: 'a4', 
      orientation: 'portrait'
    }
  }

  // Set the real order ID in the content
  const orderIdElement = document.querySelector('.order-id-placeholder')
  const originalOrderId = orderIdElement.innerText
  orderIdElement.innerText = orderId.value

  html2pdf().set(opt).from(element).save().then(() => {
    // Restore the original order ID after saving the PDF
    orderIdElement.innerText = originalOrderId
  })
}

const closeReceipt = () => {
  isReceiptVisible.value = false
  clearOrder()
}

const orderId = ref('')

const isLowStock = (stock) => stock < 20;

const fetchCustomers = async () => {
  try {
    const response = await connection.get('/customers')
    customers.value = response.data.data
  } catch (err) {
    console.error('Error fetching customers:', err)
  }
}

const isAdmin = ref(false)

const getCategoryIcon = (name) => {
  // Simplified category mapping
  return Box // Default icon for all products
}

const getCategoryName = (name) => {
  return 'Hardware' // Default category name
}

const mapProductToCategory = (name) => {
  return 'hardware' // Default category
}

const generateQuotation = () => {
  if (orderItems.value.length > 0) {
    isReceiptVisible.value = true; // Show the receipt modal

    const element = document.getElementById('receipt-content')
    const opt = {
      margin: [10, 10, 10, 10],
      filename: `quotation-${invoiceNumber.value}.pdf`, // Filename for quotation
      image: { type: 'jpeg', quality: 1 },
      html2canvas: { 
        scale: 2,
        useCORS: true,
        logging: false
      },
      jsPDF: { 
        unit: 'mm', 
        format: 'a4', 
        orientation: 'portrait'
      }
    }

    // Temporarily set title to "QUOTATION" in the content
    const titleElement = document.querySelector('.text-2xl.font-bold.text-gray-700')
    const originalTitle = titleElement.innerText
    titleElement.innerText = 'QUOTATION'

    html2pdf().set(opt).from(element).save().then(() => {
      // Restore the original title after saving the PDF
      titleElement.innerText = originalTitle
    }).catch((error) => {
      console.error('Error generating quotation:', error)
      Swal.fire({
        title: 'Error!',
        text: 'Failed to generate the quotation. Please try again.',
        icon: 'error',
        background: '#1e293b',
        color: '#ffffff'
      })
    })
  } else {
    Swal.fire({
      title: 'Cart is Empty',
      text: 'Please add items to the cart before generating a quotation.',
      icon: 'warning',
      background: '#1e293b',
      color: '#ffffff'
    })
  }
}

onMounted(async () => {
  // Get user data first
  getUserData()
  // Get admin status from localStorage
  isAdmin.value = localStorage.getItem('isAdmin') === 'true'
  // Fetch data
  await fetchProducts()
  await fetchCustomers()
  
  // Generate order ID
  orderId.value = '#' + Math.floor(10000 + Math.random() * 90000)
  invoiceNumber.value = Math.floor(10000 + Math.random() * 90000).toString()
  transactionId.value = '00000' + Math.floor(1000 + Math.random() * 9000).toString()

  const now = new Date()
  currentDate.value = `${now.toLocaleString('default', { month: 'long' })} ${now.getDate()}, ${now.getFullYear()}`
})

const isCustomerModalOpen = ref(false)
const customerSearchQuery = ref('')
const selectedCustomer = ref(null)
const customerName = ref('Select Customer')

const filteredCustomers = computed(() => {
  const query = customerSearchQuery.value.toLowerCase()
  return customers.value.filter(customer => 
    customer.name.toLowerCase().includes(query) || 
    customer.phone?.toLowerCase().includes(query) ||
    customer.email?.toLowerCase().includes(query)
  )
})

const selectCustomer = (customer) => {
  selectedCustomer.value = customer
  customerName.value = customer.name
  isCustomerModalOpen.value = false
}

const clearSelectedCustomer = () => {
  Swal.fire({
    title: 'Clear Customer Selection?',
    text: "Are you sure you want to remove the selected customer?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3B82F6',
    cancelButtonColor: '#6B7280',
    confirmButtonText: 'Yes, clear',
    cancelButtonText: 'Cancel',
    background: '#1e293b',
    color: '#ffffff'
  }).then((result) => {
    if (result.isConfirmed) {
      selectedCustomer.value = null;
      customerName.value = 'Select Customer';
    }
  });
}

const selectWalkInCustomer = () => {
  clearSelectedCustomer()
  isCustomerModalOpen.value = false
}

const checkDetails = ref({
  bankName: '',
  checkNumber: '',
  checkDate: new Date().toISOString().split('T')[0],
  amount: 0,
  remarks: ''
})

const showCheckForm = computed(() => selectedPaymentMethod.value === 'CHECK')

// Add this method after other methods
const handleCheckPaymentSubmit = () => {
  if (!checkDetails.value.bankName || !checkDetails.value.checkNumber) {
    Swal.fire({
      title: 'Missing Details',
      text: 'Please fill in all required fields',
      icon: 'warning',
      background: '#1e293b',
      color: '#ffffff'
    })
    return
  }
  
  // Store check details and proceed with payment
  completeOrder()
}

// Modify the watch section to handle check amount
watch(() => total.value, (newTotal) => {
  if (selectedPaymentMethod.value === 'CHECK') {
    checkDetails.value.amount = newTotal
  }
})
</script>

<template>
  <div class="min-h-screen bg-[#0f172a] text-white font-sans">
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

    <div class="w-full pt-24 px-4 max-w-[1920px] mx-auto">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="w-full md:w-[75%] lg:w-[80%]">
          <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
              <h1 class="text-2xl font-bold text-[#4dabf7] flex items-center gap-2">
                <ShoppingCart class="w-6 h-6" />
                Place Order
              </h1>
              <p class="text-gray-300 text-sm mt-1">Create a new order for your customers</p>
            </div>

            <div class="relative w-full sm:w-auto">
              <div class="relative">
                <input type="text" v-model="searchQuery" placeholder="Search products..."
                  class="bg-[#1e293b] border border-[#334155] rounded-lg pl-10 pr-4 py-2.5 w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-[#3b82f6]/30 text-white" />
                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
              </div>
            </div>
          </div>

          <!-- Products Grid -->
          <div class="bg-[#1a2234] p-4 rounded-lg shadow-md">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-2 bg-blue-500/20 text-blue-400 rounded-lg">
                <Package class="w-5 h-5" />
              </div>
              <h2 class="font-bold text-gray-200 text-lg">Products</h2>
              <div class="ml-auto text-sm text-gray-400">
                {{ filteredProducts.length }} items found
              </div>
            </div>

            <div v-if="loading" class="flex justify-center items-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>
            
            <div v-else-if="error" class="text-center py-12 text-red-500">
              {{ error }}
            </div>
            
            <div v-else-if="filteredProducts.length === 0" class="text-center py-12 text-gray-500 bg-gray-800/20 rounded-lg">
              <Package class="w-16 h-16 mx-auto mb-4 opacity-30" />
              <p class="text-lg font-medium">No products found</p>
              <p class="text-sm mt-2">Try changing your search term</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
              <!-- Product Card (without image) -->
              <div v-for="product in filteredProducts" :key="product.id" @click="addToOrder(product)" 
                :class="[
                  'bg-gradient-to-br rounded-lg overflow-hidden cursor-pointer transition-all duration-200 border hover:shadow-lg group relative hover:translate-y-[-2px]',
                  {
                    'from-red-500/10 to-red-900/10 border-red-500/50': product.stock === 0,
                    'from-yellow-500/10 to-yellow-900/10 border-yellow-500/50': product.stock > 0 && product.stock < 20,
                    'from-[#1e293b] to-[#1a2234] border-[#334155] hover:border-[#475569]': product.stock >= 20
                  }
                ]">
                
                <!-- Category Indicator -->
                <div class="absolute top-0 left-0 w-1 h-full bg-[#334155]"></div>
                
                <!-- Card Content -->
                <div class="p-4 pl-5">
                  <!-- Header with Category -->
                  <div class="flex items-center justify-between mb-3">
                    <div class="bg-gray-700/50 text-gray-300 text-xs px-2 py-1 rounded-full flex items-center gap-1">
                      <component :is="getCategoryIcon(mapProductToCategory(product.name.toLowerCase()))" class="w-3 h-3" />
                      <span>{{ getCategoryName(mapProductToCategory(product.name.toLowerCase())) }}</span>
                    </div>
                    
                    <!-- Stock Indicator with updated styling -->
                    <div :class="[
                      'text-xs px-2 py-1 rounded-full flex items-center gap-1',
                      {
                        'bg-red-500/20 text-red-400': product.stock === 0,
                        'bg-yellow-500/20 text-yellow-400': product.stock > 0 && product.stock < 20,
                        'bg-green-500/20 text-green-400': product.stock >= 20
                      }
                    ]">
                      <Layers class="w-3 h-3" />
                      <span>
                        {{ product.stock === 0 ? 'Out of Stock' : 
                           product.stock < 20 ? `Low Stock: ${product.stock}` : 
                           `In Stock: ${product.stock}` }}
                      </span>
                    </div>
                  </div>
                  
                  <!-- Product ID -->
                  <div class="text-xs text-gray-500 mb-1">ID: #{{ product.id.toString().padStart(4, '0') }}</div>
                  
                  <!-- Product Name -->
                  <div class="font-medium text-white text-lg mb-2">{{ product.name }}</div>
                  <div class="text-sm text-gray-400 mb-3">{{ product.brand_name }}</div>
                  <div class="text-sm text-gray-400 mb-3">Size: {{ product.size }}</div>
                  <div class="text-sm text-gray-400 mb-3">{{ product.description }}</div> <!-- Add this line -->
                  <!-- Divider -->
                  <div class="h-px bg-gradient-to-r from-transparent via-gray-700 to-transparent my-3"></div>
                  
                  <!-- Price and Add Button -->
                  <div class="flex justify-between items-center">
                    <div>
                      <div class="text-xs text-gray-400 mb-1">Price</div>
                      <div class="text-blue-400 font-bold text-lg">Rs. {{ Number(product.price).toLocaleString() }}</div> <!-- Display adjusted price -->
                    </div>
                    
                    <button :class="[
                      'p-2 rounded-lg transition-colors duration-200 text-white flex items-center gap-2',
                      product.stock === 0 ? 'bg-red-500 hover:bg-red-600' : 'bg-blue-500 hover:bg-blue-600'
                    ]">
                      <Plus class="w-4 h-4" />
                      <span>Add</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="w-full md:w-[25%] lg:w-[20%]">
          <div
            class="bg-[#1a2234] rounded-lg border border-[#334155] flex flex-col h-[calc(100vh-100px)] sticky top-24 shadow-lg">
            <div class="p-4 border-b border-gray-700/50 bg-[#1e293b]">
              <div class="flex justify-between items-center mb-3">
                <h2 class="font-bold text-gray-200 text-base flex items-center gap-1.5">
                  <ShoppingCart class="w-4 h-4 text-blue-400" />
                  Order Summary
                </h2>
              </div>

              <div class="mb-3">
                <label class="block text-xs text-gray-400 mb-1.5 flex items-center justify-between">
                  <div class="flex items-center gap-1">
                    <User class="w-3 h-3" />
                    <span>Customer Information</span>
                  </div>
                  <div class="flex items-center gap-2">
                    <button @click="navigateToCustomers" 
                            title="Add New Customer"
                            class="p-1 hover:bg-gray-700/50 rounded-full transition-colors group">
                      <UserPlusIcon class="w-4 h-4 text-blue-400 group-hover:scale-110 transition-transform" />
                    </button>
                    <button @click="isCustomerModalOpen = true"
                            class="text-xs bg-blue-500/20 text-blue-400 px-2 py-1 rounded-full hover:bg-blue-500/30 transition-colors">
                      Select Customer
                    </button>
                  </div>
                </label>
                <div class="bg-gray-700/50 border border-gray-600/50 rounded-lg px-3 py-2">
                  <div class="flex items-center justify-between">
                    <div>
                      <div class="text-sm text-white font-medium flex items-center gap-2">
                        {{ selectedCustomer ? selectedCustomer.name : 'No Customer Selected' }}
                        <span v-if="!selectedCustomer" 
                              class="text-xs bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded-full">
                          Please select a customer
                        </span>
                      </div>
                      <div v-if="selectedCustomer" class="text-xs text-gray-400 mt-1 space-y-0.5">
                        <div v-if="selectedCustomer.phone" class="flex items-center gap-1">
                          <phone-icon class="w-3 h-3" />
                          {{ selectedCustomer.phone }}
                        </div>
                        <div v-if="selectedCustomer.email" class="flex items-center gap-1">
                          <mail-icon class="w-3 h-3" />
                          {{ selectedCustomer.email }}
                        </div>
                      </div>
                    </div>
                    <button v-if="selectedCustomer"
                            @click="clearSelectedCustomer"
                            class="p-1.5 hover:bg-gray-600/50 rounded-full group"
                            title="Clear Selection">
                      <X class="w-4 h-4 text-gray-400 group-hover:text-red-400 transition-colors" />
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex-1 overflow-auto p-4 custom-scrollbar">
              <div v-if="orderItems.length === 0" class="text-center py-10 text-gray-500">
                <ShoppingCart class="w-12 h-12 mx-auto mb-3 opacity-30" />
                <p class="text-sm font-medium">Your cart is empty</p>
                <p class="text-xs mt-1">Click on products to add them to your order</p>
              </div>

              <div v-else class="space-y-3">
                <div v-for="item in orderItems" :key="item.id"
                  class="bg-gray-700/20 rounded-lg p-3 border border-gray-700/30 hover:border-gray-600/50 transition-colors duration-200">
                  <div class="flex-1 min-w-0">
                    <div class="flex justify-between">
                      <div class="font-medium text-sm truncate text-white">{{ item.name }}</div>
                      <div class="text-blue-400 text-xs font-bold">Rs. {{ item.price.toLocaleString() }}</div>
                    </div>

                    <div class="flex items-center gap-2 mt-2">
                      <div class="text-xs text-gray-400">Discount:</div>
                      <div class="flex items-center gap-1">
                        <input 
                          type="number" 
                          v-model.number="item.productDiscount" 
                          @input="updateProductDiscount(item, item.productDiscount)"
                          class="w-14 bg-gray-700/50 border border-gray-600/50 rounded-lg px-2 py-1 text-xs text-white focus:outline-none focus:ring-1 focus:ring-blue-400/30 text-center"
                          min="0"
                          max="100"
                        />
                        <span class="text-xs text-gray-400">%</span>
                      </div>
                      <div class="text-xs text-gray-400">
                        Price after discount: <span class="text-green-400">Rs. {{ (item.price * (1 - item.productDiscount / 100)).toLocaleString() }}</span>
                      </div>
                    </div>

                    <div class="flex justify-between items-center mt-3">
                      <div class="flex items-center gap-1 bg-gray-800/70 rounded-lg p-1">
                        <button @click.stop="updateQuantity(item, -1)"
                          class="p-1 hover:bg-gray-700 rounded transition-colors">
                          <Minus class="w-3 h-3" />
                        </button>
                        <input type="number" v-model.number="item.quantity" @blur="validateQuantity(item)"
                          class="w-14 bg-gray-700/50 border border-gray-600/50 rounded-lg px-2 py-1 text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-400/30 text-center" />
                        <button @click.stop="updateQuantity(item, 1)"
                          class="p-1 hover:bg-gray-700 rounded transition-colors">
                          <Plus class="w-3 h-3" />
                        </button>
                      </div>

                      <!-- Add Dimensions Button -->
                      <button @click.stop="openDimensionsModal(item)"
                        class="p-1.5 text-blue-400 hover:bg-blue-400/10 rounded transition-colors">
                        <div class="flex items-center gap-2">
                          <Ruler class="w-3.5 h-3.5" />
                          <div class="text-xs">
                            <template v-if="item.dimensions">
                              <div>{{ `${item.width}" × ${item.height}"` }}</div>
                              <div class="text-green-400">{{ `${item.totalAreaMeters} m²` }}</div>
                            </template>
                            <span v-else>Set Size</span>
                          </div>
                        </div>
                      </button>

                      <div class="text-gray-300 text-xs font-medium">
                        Rs. {{ getItemTotal(item).toLocaleString() }}
                      </div>

                      <button @click.stop="removeFromOrder(item.id)"
                        class="p-1.5 text-red-400 hover:bg-red-400/10 rounded transition-colors">
                        <Trash2 class="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="p-4 border-t border-gray-700/50 bg-[#1e293b]">
              <div class="space-y-2 mb-4 text-sm">
                <div class="flex justify-between text-gray-300">
                  <span>Subtotal</span>
                  <span>Rs. {{ subtotal.toLocaleString() }}</span>
                </div>
                
                <div class="bg-gray-700/30 rounded-lg p-3 border border-gray-600/50">
                  <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2">
                      <div class="p-1.5 bg-blue-500/20 rounded-md">
                        <Percent class="w-4 h-4 text-blue-400" />
                      </div>
                      <label for="apply-discount" class="text-gray-200 font-medium cursor-pointer">
                        Discount Options
                      </label>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                      <input type="checkbox" id="apply-discount" v-model="applyDiscount" 
                             class="sr-only peer">
                      <div class="w-11 h-6 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full 
                                  peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 
                                  after:left-[2px] after:bg-white after:border-gray-300 after:border 
                                  after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500">
                      </div>
                    </label>
                  </div>
                  
                  <transition name="fade">
                    <div v-if="applyDiscount" class="mt-3 pt-3 border-t border-gray-600/50">
                      <div class="flex flex-col gap-2">
                        <div class="flex items-center justify-between gap-3">
                          <div class="flex-1">
                            <input type="range" v-model="customDiscountRate" min="0" max="100" step="5"
                                   class="w-full h-2 bg-gray-600 rounded-lg appearance-none cursor-pointer
                                          accent-blue-500" />
                          </div>
                          <div class="flex items-center gap-2 min-w-[80px]">
                            <input type="number" v-model="customDiscountRate" min="0" max="100"
                                   class="w-14 bg-gray-700/50 border border-gray-600/50 rounded-lg px-2 py-1 
                                          text-sm text-white focus:outline-none focus:ring-1 focus:ring-blue-400/30 
                                          text-center" />
                            <span class="text-gray-400">%</span>
                          </div>
                        </div>
                        <div class="flex justify-between text-sm">
                          <span class="text-gray-400">Discount Amount:</span>
                          <span class="text-blue-400 font-medium">Rs. {{ discount.toLocaleString() }}</span>
                        </div>
                      </div>
                    </div>
                  </transition>
                </div>

                <div class="flex justify-between font-bold pt-2 border-t border-gray-700/50 text-white">
                  <span>Total</span>
                  <span>Rs. {{ total.toLocaleString() }}</span>
                </div>
              </div>

              <div class="grid grid-cols-3 gap-2">
                <button @click="clearOrder"
                  class="py-2 px-3 bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5 text-sm">
                  <Trash2 class="w-4 h-4" />
                  <span>Clear</span>
                </button>

                <!-- New Quotation Button -->
                <button @click="generateQuotation"
                  class="py-2 px-3 bg-yellow-500/20 text-yellow-400 hover:bg-yellow-500/30 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5 text-sm">
                  <FileText class="w-4 h-4" />
                  <span>Quotation</span>
                </button>

                <button @click="openPaymentModal" :disabled="orderItems.length === 0" :class="[ 
                  'py-2 px-3 rounded-lg transition-colors duration-200 flex items-center justify-center gap-1.5 text-sm',
                  orderItems.length === 0
                    ? 'bg-gray-700/50 text-gray-500 cursor-not-allowed'
                    : 'bg-blue-500 hover:bg-blue-600 text-white'
                ]">
                  <CreditCard class="w-4 h-4" />
                  <span>Checkout</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="isPaymentModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="isPaymentModalOpen = false"></div>

      <div class="relative bg-gray-800 rounded-lg shadow-xl border border-gray-700/50 w-full max-w-md p-6 z-10 animate-scale-in">
        <div class="flex justify-between items-center mb-5">
          <h2 class="text-xl font-bold text-gray-200">Complete Payment</h2>
          <button @click="isPaymentModalOpen = false" class="p-1.5 hover:bg-gray-700/50 rounded-full transition-colors">
            <X class="w-5 h-5" />
          </button>
        </div>

        <div class="mb-5">
          <div class="flex items-center justify-between mb-3">
            <div class="text-sm text-gray-400">Order Total</div>
            <div class="text-2xl font-bold text-white">{{ formatCurrency(total) }}</div>
          </div>

          <!-- Add Payment Method Selection -->
          <div class="bg-gray-700/30 rounded-lg p-4 mb-4">
            <label class="text-sm text-gray-400 mb-3 block">Payment Method</label>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
              <button 
                v-for="method in ['CASH', 'CARD', 'CHECK', 'ONLINE']" 
                :key="method"
                @click="selectedPaymentMethod = method"
                :class="[
                  'p-3 rounded-lg border transition-all duration-200 flex flex-col items-center gap-2',
                  selectedPaymentMethod === method 
                    ? 'bg-blue-500/20 border-blue-500/50 text-white' 
                    : 'bg-gray-700/50 border-gray-600/50 text-gray-400 hover:bg-gray-600/50'
                ]"
              >
                <component :is="{
                  'CASH': Banknote,
                  'CARD': CreditCard,
                  'CHECK': FileText,
                  'ONLINE': FileText
                }[method]" class="w-5 h-5" />
                <span class="text-sm">{{ method }}</span>
              </button>
            </div>
          </div>

          <!-- Advanced Payment Toggle -->
          <div class="bg-gray-700/30 rounded-lg p-4">
            <label class="flex items-center justify-between">
              <span class="text-sm text-gray-300">Enable Advance Payment</span>
              <div class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" v-model="isAdvancePayment" class="sr-only peer" />
                <div class="w-11 h-6 bg-gray-600 rounded-full peer peer-checked:after:translate-x-full 
                          peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 
                          after:left-[2px] after:bg-white after:border-gray-300 after:border 
                          after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-500">
                </div>
              </div>
            </label>

            <transition name="fade">
              <div v-if="isAdvancePayment" class="mt-4 space-y-4">
                <div>
                  <label class="text-sm text-gray-400 mb-2 block">Advance Amount</label>
                  <div class="relative">
                    <input 
                      type="number"
                      v-model="advanceAmount"
                      :max="total"
                      class="w-full bg-gray-600/50 border border-gray-500 rounded-lg px-3 py-2 text-white"
                      @input="advanceAmount = Math.min(Math.max(0, advanceAmount), total)"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                      {{ ((advanceAmount / total) * 100).toFixed(0) }}%
                    </div>
                  </div>

                  <!-- Payment Progress Bar -->
                  <div class="mt-2 bg-gray-600 rounded-full h-2 overflow-hidden">
                    <div class="bg-blue-500 h-full transition-all duration-300"
                         :style="{ width: `${(advanceAmount / total) * 100}%` }">
                    </div>
                  </div>

                  <!-- Payment Breakdown -->
                  <div class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-400">
                      <span>Advance Payment:</span>
                      <span class="text-blue-400">{{ formatCurrency(advanceAmount) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-400">
                      <span>Remaining Balance:</span>
                      <span class="text-yellow-400">{{ formatCurrency(remainingBalance) }}</span>
                    </div>
                    <div class="flex justify-between text-gray-400 pt-2 border-t border-gray-600">
                      <span>Payment Status:</span>
                      <span :class="{
                        'text-green-400': paymentStatus === 'PAID',
                        'text-yellow-400': paymentStatus === 'PARTIALLY_PAID',
                        'text-red-400': paymentStatus === 'PENDING'
                      }">
                        {{ paymentStatus.replace('_', ' ') }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </transition>
          </div>
        </div>

        <!-- Add this inside your payment modal, after the payment method selection -->
        <transition name="fade">
          <div v-if="showCheckForm" class="mt-4 bg-gray-700/30 rounded-lg p-4 space-y-4">
            <div class="text-sm font-medium text-gray-300 mb-2">Check Payment Details</div>
            
            <div class="space-y-3">
              <div>
                <label class="block text-sm text-gray-400 mb-1">Bank Name</label>
                <input 
                  type="text"
                  v-model="checkDetails.bankName"
                  class="w-full bg-gray-600/50 border border-gray-500 rounded-lg px-3 py-2 text-white placeholder-gray-400"
                  placeholder="Enter bank name"
                />
              </div>

              <div>
                <label class="block text-sm text-gray-400 mb-1">Check Number</label>
                <input 
                  type="text"
                  v-model="checkDetails.checkNumber"
                  class="w-full bg-gray-600/50 border border-gray-500 rounded-lg px-3 py-2 text-white placeholder-gray-400"
                  placeholder="Enter check number"
                />
              </div>

              <div>
                <label class="block text-sm text-gray-400 mb-1">Check Date</label>
                <input 
                  type="date"
                  v-model="checkDetails.checkDate"
                  class="w-full bg-gray-600/50 border border-gray-500 rounded-lg px-3 py-2 text-white"
                />
              </div>

              <div>
                <label class="block text-sm text-gray-400 mb-1">Amount</label>
                <input 
                  type="number"
                  v-model="checkDetails.amount"
                  readonly
                  class="w-full bg-gray-600/50 border border-gray-500 rounded-lg px-3 py-2 text-white cursor-not-allowed"
                />
              </div>

              <div>
                <label class="block text-sm text-gray-400 mb-1">Remarks (Optional)</label>
                <textarea 
                  v-model="checkDetails.remarks"
                  rows="2"
                  class="w-full bg-gray-600/50 border border-gray-500 rounded-lg px-3 py-2 text-white placeholder-gray-400"
                  placeholder="Add any additional notes"
                ></textarea>
              </div>
            </div>
          </div>
        </transition>

        <div class="flex gap-3">
          <button @click="isPaymentModalOpen = false"
            class="flex-1 py-3 px-4 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors duration-200 text-sm text-white">
            Cancel
          </button>
          <button 
            @click="selectedPaymentMethod === 'CHECK' ? handleCheckPaymentSubmit() : completeOrder()"
            :disabled="isProcessingOrder || (isAdvancePayment && advanceAmount <= 0) || (selectedPaymentMethod === 'CHECK' && (!checkDetails.bankName || !checkDetails.checkNumber))"
            class="flex-1 py-3 px-4 bg-blue-500 hover:bg-blue-600 rounded-lg transition-colors duration-200 
                   flex items-center justify-center gap-2 text-sm text-white disabled:opacity-50 
                   disabled:cursor-not-allowed disabled:hover:bg-blue-500">
            <template v-if="isProcessingOrder">
              <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>Processing...</span>
            </template>
            <template v-else>
              <Check class="w-5 h-5" />
              <span>{{ selectedPaymentMethod === 'CHECK' ? 'Process Check Payment' : (isAdvancePayment ? 'Record Advance Payment' : 'Complete Order') }}</span>
            </template>
          </button>
        </div>
      </div>
    </div>

    <div v-if="isReceiptVisible" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeReceipt"></div>

      <div
        class="relative bg-white rounded-lg shadow-xl w-full max-w-[800px] z-10 animate-scale-in overflow-auto max-h-[90vh]">
        <div id="receipt-content" class="p-8 text-gray-800">
          <!-- Header Section -->
          <div class="border-b-2 border-gray-300 pb-8">
            <div class="flex justify-between items-start">
              <div>
                <div class="text-3xl font-bold text-blue-600">HARDWARE SUPPLY</div>
                <div class="text-sm text-gray-600 mt-2">
                  102 Railway Ave.<br>
                  Kandy, Sri Lanka<br>
                  Phone: (94) 81 123 4567<br>
                  Email: info@hardwaresupply.com
                </div>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold text-gray-700">INVOICE</div>
                <div class="mt-2 text-sm">
                  <div class="text-gray-600">Order ID: <span class="text-gray-800 font-medium order-id-placeholder">{{ orderId }}</span></div> <!-- Updated to use a placeholder -->
                  <div class="text-gray-600">Date: <span class="text-gray-800">{{ currentDate }}</span></div>
                  <div class="text-gray-600">Transaction ID: <span class="text-gray-800">{{ transactionId }}</span></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Billing Information -->
          <div class="grid grid-cols-2 gap-12 py-8">
            <div>
              <div class="text-sm text-gray-600 uppercase tracking-wider mb-2">Bill To</div>
              <div class="text-base font-bold text-gray-800">{{ customerName }}</div>
            </div>
            <div>
              <div class="text-sm text-gray-600 uppercase tracking-wider mb-2">Payment Details</div>
              <div class="space-y-1 text-sm">
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Payment Status:</span>
                  <span :class="{
                    'text-green-600': paymentStatus === 'FULL_PAYMENT' || paymentStatus === 'PAID',
                    'text-yellow-600': paymentStatus === 'PARTIALLY_PAID',
                    'text-red-600': paymentStatus === 'PENDING'
                  }">{{ paymentStatus.replace('_', ' ') }}</span>
                </div>
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Payment Method:</span>
                  <span class="text-gray-800 font-medium">
                    {{ {
                      'CASH': 'Cash Payment',
                      'CARD': 'Card Payment',
                      'CHECK': 'Check Payment',
                      'ONLINE': 'Online Payment'
                    }[selectedPaymentMethod] }}
                  </span>
                </div>
                
                <!-- Add Advance Payment Details -->
                <template v-if="isAdvancePayment">
                  <div class="grid grid-cols-2">
                    <span class="text-gray-600">Total Amount:</span>
                    <span class="text-gray-800">{{ formatCurrency(total) }}</span>
                  </div>
                  <div class="grid grid-cols-2">
                    <span class="text-gray-600">Advance Paid:</span>
                    <span class="text-green-600 font-medium">{{ formatCurrency(advanceAmount) }}</span>
                  </div>
                  <div class="grid grid-cols-2">
                    <span class="text-gray-600">Balance Due:</span>
                    <span class="text-red-600 font-medium">{{ formatCurrency(remainingBalance) }}</span>
                  </div>
                  <div class="grid grid-cols-2">
                    <span class="text-gray-600">Payment Progress:</span>
                    <span class="text-gray-800">{{ ((advanceAmount / total) * 100).toFixed(0) }}%</span>
                  </div>
                </template>

                <!-- Regular Payment Terms -->
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Payment Terms:</span>
                  <span class="text-gray-800">{{ isAdvancePayment ? 'Partial Payment' : 'Net 30' }}</span>
                </div>
                <div class="grid grid-cols-2">
                  <span class="text-gray-600">Due Date:</span>
                  <span class="text-gray-800">{{ new Date(new Date().setDate(new Date().getDate() + 30)).toLocaleDateString() }}</span>
                </div>
              </div>

              <!-- Add Payment Progress Bar for Advance Payments -->
              <div v-if="isAdvancePayment" class="mt-4">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div class="h-full bg-blue-500 transition-all duration-300"
                       :style="{ width: `${(advanceAmount / total) * 100}%` }"
                       :class="{ 
                         'bg-green-500': paymentStatus === 'PAID',
                         'bg-yellow-500': paymentStatus === 'PARTIALLY_PAID',
                         'bg-red-500': paymentStatus === 'PENDING'
                       }">
                  </div>
                </div>
                <div class="text-xs text-gray-500 mt-1 text-center">
                  Payment Progress: {{ ((advanceAmount / total) * 100).toFixed(0) }}% Complete
                </div>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="mt-8">
            <table class="w-full">
              <thead>
                <tr class="bg-gray-100">
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Code</th>
                  <th class="py-3 px-4 text-left text-xs font-medium text-gray-600 uppercase tracking-wider">Description</th>
                  <th class="py-3 px-4 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Dimension - Inches</th>
                  <th class="py-3 px-4 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Rate</th>
                  <th class="py-3 px-4 text-center text-xs font-medium text-gray-600 uppercase tracking-wider">Qty</th>
                  <th class="py-3 px-4 text-right text-xs font-medium text-gray-600 uppercase tracking-wider">Amount</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="item in orderItems" :key="item.id" class="text-sm">
                  <td class="py-4 px-4 text-gray-800">{{ item.id.toString().padStart(4, '0') }}</td>
                  <td class="py-4 px-4">
                    <div class="font-medium text-gray-800">{{ item.name }}</div>
                    <div class="text-gray-500 text-xs">{{ item.description || '-' }}</div>
                  </td>
                  <td class="py-4 px-4 text-right text-gray-800">
                    <template v-if="item.dimensions">
                      <div>{{ `${item.width}" × ${item.height}"` }}</div>
                      <div class="text-sm text-green-600">{{ `${item.totalAreaMeters} m²` }}</div>
                    </template>
                    <span v-else>-</span>
                  </td>
                  <td class="py-4 px-4 text-right text-gray-800">
                    Rs. {{ (item.price * (1 - item.productDiscount / 100)).toLocaleString() }}
                    <div v-if="item.productDiscount > 0" class="text-xs text-red-600">
                      (-{{ item.productDiscount }}%)
                    </div>
                  </td>
                  <td class="py-4 px-4 text-center text-gray-800">{{ item.quantity }}</td>
                  <td class="py-4 px-4 text-right font-medium text-gray-800">
                    Rs. {{ getItemTotal(item).toLocaleString() }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Summary -->
          <div class="mt-8 border-t border-gray-200 pt-8">
            <div class="flex justify-end">
              <div class="w-72">
                <div class="space-y-2">
                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal (Before Discounts)</span>
                    <span class="text-gray-800 font-medium">Rs. {{ orderItems.reduce((total, item) => total + (item.price * item.quantity), 0).toLocaleString() }}</span>
                  </div>
                  
                  <div class="flex justify-between text-sm" v-if="orderItems.some(item => item.productDiscount > 0)">
                    <span class="text-gray-600">Product Discounts</span>
                    <span class="text-red-600 font-medium">
                      - Rs. {{ (orderItems.reduce((total, item) => total + (item.price * item.quantity * item.productDiscount / 100), 0)).toLocaleString() }}
                    </span>
                  </div>

                  <div class="flex justify-between text-sm">
                    <span class="text-gray-600">Subtotal (After Product Discounts)</span>
                    <span class="text-gray-800 font-medium">Rs. {{ subtotal.toLocaleString() }}</span>
                  </div>

                  <div v-if="applyDiscount" class="flex justify-between text-sm">
                    <span class="text-gray-600">Cart Discount ({{ customDiscountRate }}%)</span>
                    <span class="text-red-600 font-medium">- Rs. {{ discount.toLocaleString() }}</span>
                  </div>

                  <div class="flex justify-between text-base font-bold border-t border-gray-300 pt-2 mt-2">
                    <span class="text-gray-800">Final Total</span>
                    <span class="text-blue-600">Rs. {{ total.toLocaleString() }}</span>
                  </div>

                  <div class="text-xs text-gray-500 mt-2">
                    Total Savings: Rs. {{ (
                      orderItems.reduce((total, item) => total + (item.price * item.quantity * item.productDiscount / 100), 0) + 
                      (applyDiscount ? discount : 0)
                    ).toLocaleString() }}
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="mt-12 pt-8 border-t border-gray-200">
            <div class="text-center text-gray-600 text-sm">
              <div class="font-medium text-gray-800 mb-2">Thank you for your business!</div>
              <div>For any inquiries, please contact us at support@hardwaresupply.com</div>
              <div class="mt-4 text-xs text-gray-500">
                This is a computer-generated invoice and requires no signature.
              </div>
            </div>
          </div>

          <!-- Terms and Conditions -->
          <div class="mt-8 text-xs text-gray-500">
            <div class="font-medium text-gray-600 mb-1">Terms & Conditions:</div>
            <ul class="list-disc pl-4 space-y-1">
              <li>Payment is due within 30 days</li>
              <li>Please include invoice number on your payment</li>
              <li>All prices are in Sri Lankan Rupees (Rs.)</li>
            </ul>
          </div>
        </div>

        <div class="sticky bottom-0 bg-white p-4 border-t border-gray-200">
          <button @click="printReceipt"
            class="w-full py-2.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
            <Printer class="w-5 h-5" />
            <span>Print Receipt</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Customer Selection Modal -->
    <div v-if="isCustomerModalOpen" 
         class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" 
           @click="isCustomerModalOpen = false"></div>
      
      <div class="relative bg-gray-800 rounded-lg shadow-xl w-full max-w-3xl z-10 animate-scale-in">
        <!-- Modal Header -->
        <div class="p-6 border-b border-gray-700">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-xl font-bold text-white">Customer Selection</h2>
              <p class="text-gray-400 text-sm mt-1">Select a customer for this transaction</p>
            </div>
            <button @click="isCustomerModalOpen = false" 
                    class="p-2 hover:bg-gray-700/50 rounded-full transition-colors">
              <X class="w-5 h-5" />
            </button>
          </div>
          
          <!-- Search Bar -->
          <div class="mt-6 relative">
            <input type="text"
                   v-model="customerSearchQuery"
                   placeholder="Search by name, phone, or email..."
                   class="w-full bg-gray-700/50 border border-gray-600/50 rounded-lg pl-12 pr-4 py-3 text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30" />
            <Search class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
          </div>
        </div>
        
        <!-- Customer List -->
        <div class="p-6 max-h-[60vh] overflow-auto custom-scrollbar">
          <div v-if="filteredCustomers.length === 0" 
               class="text-center py-8">
            <User class="w-12 h-12 mx-auto text-gray-500 mb-3" />
            <p class="text-gray-400 font-medium">No customers found</p>
            <p class="text-gray-500 text-sm mt-1">Try adjusting your search terms</p>
          </div>
          
          <div v-else class="grid gap-3">
            <button v-for="customer in filteredCustomers"
                    :key="customer.id"
                    @click="selectCustomer(customer)"
                    :class="[
                      'w-full text-left p-4 rounded-lg transition-all duration-200 border',
                      selectedCustomer?.id === customer.id
                        ? 'bg-blue-500/20 border-blue-500/50'
                        : 'hover:bg-gray-700/50 border-gray-700/50 hover:border-gray-600'
                    ]">
              <div class="flex items-start gap-4">
                <!-- Customer Avatar/Icon -->
                <div :class="[
                  'p-3 rounded-full',
                  selectedCustomer?.id === customer.id
                    ? 'bg-blue-500/20 text-blue-400'
                    : 'bg-gray-700/50 text-gray-400'
                ]">
                  <User class="w-6 h-6" />
                </div>
                
                <!-- Customer Info -->
                <div class="flex-1">
                  <div class="flex items-start justify-between">
                    <div>
                      <div class="font-medium text-white text-lg">{{ customer.name }}</div>
                      <div class="grid gap-1 mt-2">
                        <div v-if="customer.phone" class="flex items-center gap-2 text-sm text-gray-400">
                          <phone-icon class="w-4 h-4" />
                          {{ customer.phone }}
                        </div>
                        <div v-if="customer.email" class="flex items-center gap-2 text-sm text-gray-400">
                          <mail-icon class="w-4 h-4" />
                          {{ customer.email }}
                        </div>
                        <div class="flex items-center gap-2 text-sm text-gray-500">
                          <tag-icon class="w-4 h-4" />
                          ID: #{{ customer.id.toString().padStart(4, '0') }}
                        </div>
                      </div>
                    </div>
                    
                    <div v-if="selectedCustomer?.id === customer.id" 
                         class="text-blue-400">
                      <Check class="w-6 h-6" />
                    </div>
                  </div>
                </div>
              </div>
            </button>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="p-6 border-t border-gray-700">
          <div class="flex justify-end gap-3">
            <button @click="isCustomerModalOpen = false"
                    class="px-4 py-2 text-gray-400 hover:text-white transition-colors">
              Cancel
            </button>
            <button @click="isCustomerModalOpen = false"
                    class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg flex items-center gap-2">
              <Check class="w-5 h-5" />
              Confirm Selection
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hover-trigger {
  background: transparent;
}

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: #4B5563 transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #4B5563;
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: #6B7280;
}

.custom-scrollbar-x {
  scrollbar-width: thin;
  scrollbar-color: #4B5563 transparent;
}

.custom-scrollbar-x::-webkit-scrollbar {
  height: 5px;
}

.custom-scrollbar-x::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb {
  background-color: #4B5563;
  border-radius: 3px;
}

.custom-scrollbar-x::-webkit-scrollbar-thumb:hover {
  background-color: #6B7280;
}

button {
  transition: all 0.2s ease;
}

button:hover:not(:disabled) {
  transform: translateY(-1px);
}

button:active:not(:disabled) {
  transform: translateY(0);
}

.animate-scale-in {
  animation: scaleIn 0.3s ease forwards;
}

.animate-fade-in {
  animation: fadeIn 0.3s ease forwards;
}

.animate-fade-out {
  animation: fadeOut 0.3s ease forwards;
}

@keyframes scaleIn {
  0% {
    opacity: 0;
    transform: scale(0.95);
  }

  100% {
    opacity: 1;
    transform: scale(1);
  }
}

@keyframes fadeIn {
  0% {
    opacity: 0;
  }

  100% {
    opacity: 1;
  }
}

@keyframes fadeOut {
  0% {
    opacity: 1;
    transform: translate(-50%, 0);
  }

  100% {
    opacity: 0;
    transform: translate(-50%, 1rem);
  }
}

.category-icon-container {
  position: relative;
  transition: transform 0.3s ease;
}

.category-icon-container:hover .category-icon {
  animation: icon-wave 2s ease-in-out infinite;
}

@keyframes icon-wave {
  0% {
    transform: translateY(0) scale(1);
  }
  25% {
    transform: translateY(-3px) scale(1.1);
  }
  50% {
    transform: translateY(0) scale(1);
  }
  75% {
    transform: translateY(3px) scale(0.95);
  }
  100% {
    transform: translateY(0) scale(1);
  }
}

[class*="category-icon"].text-blue-400 {
  animation: float 3s ease-in-out infinite;
}

@keyframes float {
  0% {
    transform: translateY(0) rotate(0);
  }
  50% {
    transform: translateY(-5px) rotate(5deg);
  }
  100% {
    transform: translateY(0) rotate(0);
  }
}

.animate-pulse {
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%, 100% {
    opacity: 0.5;
  }
  50% {
    opacity: 0.2;
  }
}

.animate-bounce {
  animation: bounce 2s infinite;
}

@keyframes bounce {
  0%, 100% {
    transform: translateY(-5%);
    animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
  }
  50% {
    transform: translateY(0);
    animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  background: #3B82F6;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
}

input[type="range"]::-webkit-slider-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);
}

input[type="range"]::-moz-range-thumb {
  width: 16px;
  height: 16px;
  background: #3B82F6;
  border-radius: 50%;
  cursor: pointer;
  transition: all 0.2s ease;
  border: none;
}

input[type="range"]::-moz-range-thumb:hover {
  transform: scale(1.1);
  box-shadow: 0 0 0 4px rgba(59,  130, 246, 0.3);
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

.animate-spin {
  animation: spin 1s linear infinite;
}

.low-stock {
  animation: pulse-warning 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse-warning {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: 0.7;
  }
}
</style>
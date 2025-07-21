<script setup>
import Header from "./Header.vue"
import Sidebar from "./Sidebar.vue"
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { 
  BuildingLibraryIcon, BanknotesIcon, 
  PencilIcon, TrashIcon, PlusIcon,
  MagnifyingGlassIcon, ArrowsRightLeftIcon,
  ChevronUpIcon, ChevronDownIcon,
  EyeIcon, XMarkIcon
} from '@heroicons/vue/24/outline'
import { connection } from '@/api/axios'
import Swal from 'sweetalert2'
import html2pdf from 'html2pdf.js'

// Formatter functions
const formatCurrency = (value) => {
  return new Intl.NumberFormat('en-LK', {
    style: 'currency',
    currency: 'LKR',
    minimumFractionDigits: 2
  }).format(value || 0)
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  }).format(date)
}

// State management
const activeTab = ref('assets')
const isLoading = ref(true)
const showForm = ref(false)
const formMode = ref('add')
const searchQuery = ref('')
const sortField = ref('name')
const sortDirection = ref('asc')
const currentItem = ref(null)
const showViewModal = ref(false)
const viewingItem = ref(null)
const showEmployerPaymentReceipt = ref(false)
const employerPaymentReceiptData = ref(null)
const employerPaymentSearchType = ref('name')
const employerPaymentSearchValue = ref('')

// Data storage
const assets = ref([])
const investments = ref([])
const loans = ref([])
const dailyExpenses = ref([])
const supplierPayments = ref([])
const employerPayments = ref([])
const cashiers = ref([])

// Form data
const formData = ref({
  // Asset fields
  name: '',
  type: '',
  location: '',
  value: '',
  
  // Investment fields
  investor_name: '',
  amount: '',
  investment_date: '',
  description: '',
  
  // Loan fields
  borrower_name: '',
  loan_date: '',
  due_date: '',
  status: 'pending',
  
  // Daily Expenses fields
  category: '',
  custom_category: '',
  date: new Date().toISOString().split('T')[0], // Default to today

  // Employer Payment fields
  cashier_id: '',
  salary_amount: '',
  payment_duration: '',
  payment_date: new Date().toISOString().split('T')[0],
  payment_method: 'cash',
  notes: ''
})

// Add validation state for asset fields
const assetFieldErrors = ref({
  name: '',
  type: '',
  location: ''
})

// Validation function for text-only fields
const validateAssetTextField = (field, value) => {
  const regex = /^[A-Za-z\s]+$/
  if (!value.trim()) {
    assetFieldErrors.value[field] = ''
  } else if (!regex.test(value.trim())) {
    assetFieldErrors.value[field] = 'Only letters and spaces are allowed'
  } else {
    assetFieldErrors.value[field] = ''
  }
}

// Add validation state for investment fields
const investmentFieldErrors = ref({
  investor_name: '',
  description: ''
})

// Validation function for text-only fields (letters and spaces)
const validateInvestmentTextField = (field, value) => {
  const regex = /^[A-Za-z\s]+$/
  if (!value.trim()) {
    investmentFieldErrors.value[field] = ''
  } else if (!regex.test(value.trim())) {
    investmentFieldErrors.value[field] = 'Only letters and spaces are allowed'
  } else {
    investmentFieldErrors.value[field] = ''
  }
}

// Add validation state for loan fields
const loanFieldErrors = ref({
  borrower_name: '',
  description: ''
})

// Validation function for loan text-only fields
const validateLoanTextField = (field, value) => {
  const regex = /^[A-Za-z\s]+$/
  if (!value.trim()) {
    loanFieldErrors.value[field] = ''
  } else if (!regex.test(value.trim())) {
    loanFieldErrors.value[field] = 'Only letters and spaces are allowed'
  } else {
    loanFieldErrors.value[field] = ''
  }
}

// Add validation state for daily expenses fields
const dailyExpensesFieldErrors = ref({
  description: ''
})

// Validation function for daily expenses description (text only)
const validateDailyExpensesTextField = (field, value) => {
  const regex = /^[A-Za-z\s]+$/
  if (!value.trim()) {
    dailyExpensesFieldErrors.value[field] = ''
  } else if (!regex.test(value.trim())) {
    dailyExpensesFieldErrors.value[field] = 'Only letters and spaces are allowed'
  } else {
    dailyExpensesFieldErrors.value[field] = ''
  }
}

// Add validation state for employer payments
const employerPaymentFieldErrors = ref({
  notes: ''
})

// Validation function for employer payments
const validateEmployerPaymentTextField = (field, value) => {
  const regex = /^[A-Za-z\s]+$/
  if (!value.trim()) {
    employerPaymentFieldErrors.value[field] = ''
  } else if (!regex.test(value.trim())) {
    employerPaymentFieldErrors.value[field] = 'Only letters and spaces are allowed'
  } else {
    employerPaymentFieldErrors.value[field] = ''
  }
}

// Computed properties
const tabHeaderInfo = computed(() => {
  switch (activeTab.value) {
    case 'assets':
      return {
        icon: BuildingLibraryIcon,
        topic: 'Assets',
        description: 'Manage your financial assets'
      }
    case 'investments':
      return {
        icon: BanknotesIcon,
        topic: 'Investments',
        description: 'Track your investments and returns'
      }
    case 'loans':
      return {
        icon: ArrowsRightLeftIcon,
        topic: 'Loans',
        description: 'Monitor loans and repayments'
      }
    case 'dailyExpenses':
      return {
        icon: ArrowsRightLeftIcon,
        topic: 'Daily Expenses',
        description: 'Record and analyze daily expenses'
      }
    case 'supplierPayments':
      return {
        icon: BanknotesIcon,
        topic: 'Supplier Payments',
        description: 'View and manage supplier payments'
      }
    case 'employerPayments':
      return {
        icon: BanknotesIcon,
        topic: 'Employer Payments',
        description: 'Manage employer salary payments'
      }
    default:
      return {
        icon: BuildingLibraryIcon,
        topic: 'Financial Details',
        description: 'Manage your financial assets'
      }
  }
})

const filteredItems = computed(() => {
  let items = []
  
  switch (activeTab.value) {
    case 'assets':
      items = assets.value
      break
    case 'investments':
      items = investments.value
      break
    case 'loans':
      items = loans.value
      break
    case 'dailyExpenses':
      items = dailyExpenses.value
      break
    case 'supplierPayments':
      items = supplierPayments.value
      break
    case 'employerPayments':
      items = employerPayments.value
      // Advanced search for employer payments
      if (!employerPaymentSearchValue.value) return items
      if (employerPaymentSearchType.value === 'name') {
        return items.filter(item =>
          item.cashier?.name?.toLowerCase().includes(employerPaymentSearchValue.value.toLowerCase())
        )
      } else if (employerPaymentSearchType.value === 'payment_method') {
        return items.filter(item =>
          item.payment_method === employerPaymentSearchValue.value
        )
      } else if (employerPaymentSearchType.value === 'payment_date') {
        // Compare only the date part (YYYY-MM-DD)
        return items.filter(item => {
          const itemDate = item.payment_date
            ? new Date(item.payment_date).toISOString().split('T')[0]
            : ''
          return itemDate === employerPaymentSearchValue.value
        })
      } else if (employerPaymentSearchType.value === 'payment_duration') {
        return items.filter(item =>
          item.payment_duration === employerPaymentSearchValue.value
        )
      }
      return items
  }
  
  if (!searchQuery.value) return items
  
  return items.filter(item => {
    if (activeTab.value === 'assets') {
      return item.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.type.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.location.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else if (activeTab.value === 'investments') {
      return item.investor_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else if (activeTab.value === 'loans') {
      return item.borrower_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.status.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else if (activeTab.value === 'dailyExpenses') {
      return item.category.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.custom_category?.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.description?.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else if (activeTab.value === 'supplierPayments') {
      return item.supplier?.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.product?.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.payment_status.toLowerCase().includes(searchQuery.value.toLowerCase())
    } else if (activeTab.value === 'employerPayments') {
      return item.employee_name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
             item.notes?.toLowerCase().includes(searchQuery.value.toLowerCase())
    }
    return false
  })
})

const sortedItems = computed(() => {
  return [...filteredItems.value].sort((a, b) => {
    let aValue = a[sortField.value]
    let bValue = b[sortField.value]
    
    if (!isNaN(parseFloat(aValue)) && !isNaN(parseFloat(bValue))) {
      aValue = parseFloat(aValue)
      bValue = parseFloat(bValue)
    } else if (typeof aValue === 'string' && typeof bValue === 'string') {
      aValue = aValue.toLowerCase()
      bValue = bValue.toLowerCase()
    }
    
    if (aValue < bValue) return sortDirection.value === 'asc' ? -1 : 1
    if (aValue > bValue) return sortDirection.value === 'asc' ? 1 : -1
    return 0
  })
})

const totalValue = computed(() => {
  if (activeTab.value === 'assets') {
    return assets.value.reduce((sum, asset) => sum + parseFloat(asset.value || 0), 0)
  } else if (activeTab.value === 'investments') {
    return investments.value.reduce((sum, investment) => sum + parseFloat(investment.amount || 0), 0)
  } else if (activeTab.value === 'loans') {
    return loans.value.reduce((sum, loan) => sum + parseFloat(loan.amount || 0), 0)
  } else if (activeTab.value === 'dailyExpenses') {
    return dailyExpenses.value.reduce((sum, expense) => sum + parseFloat(expense.amount || 0), 0)
  } else if (activeTab.value === 'supplierPayments') {
    return supplierPayments.value.reduce((sum, group) => sum + parseFloat(group.total_cost || 0), 0)
  } else if (activeTab.value === 'employerPayments') {
    return Array.isArray(employerPayments.value) 
      ? employerPayments.value.reduce((sum, payment) => sum + parseFloat(payment.salary_amount || 0), 0)
      : 0
  }
  return 0
})

const formTitle = computed(() => {
  const action = formMode.value === 'add' ? 'Add' : 'Edit'
  const type = activeTab.value.charAt(0).toUpperCase() + activeTab.value.slice(1, -1)
  if (activeTab.value === 'supplierPayments') return 'Supplier Payment'
  if (activeTab.value === 'employerPayments') return 'Employer Payment'
  return `${action} ${type}`
})

// Update isValidAssetForm to include text-only validation
const isValidAssetForm = computed(() => {
  if (activeTab.value !== 'assets') return false
  // Validate all three fields
  ['name', 'type', 'location'].forEach(field => validateAssetTextField(field, formData.value[field]))
  return (
    !assetFieldErrors.value.name &&
    !assetFieldErrors.value.type &&
    !assetFieldErrors.value.location &&
    formData.value.name?.trim() &&
    formData.value.type?.trim() &&
    formData.value.location?.trim() &&
    formData.value.value &&
    Number(formData.value.value) > 0
  )
})

// Update isValidInvestmentForm to include text-only validation
const isValidInvestmentForm = computed(() => {
  if (activeTab.value !== 'investments') return false
  validateInvestmentTextField('investor_name', formData.value.investor_name)
  validateInvestmentTextField('description', formData.value.description || '')
  return (
    !investmentFieldErrors.value.investor_name &&
    !investmentFieldErrors.value.description &&
    formData.value.investor_name?.trim() &&
    formData.value.amount &&
    Number(formData.value.amount) > 0 &&
    formData.value.investment_date
  )
})

// Update isValidLoanForm to require loan_date < due_date
const isValidLoanForm = computed(() => {
  if (activeTab.value !== 'loans') return false
  validateLoanTextField('borrower_name', formData.value.borrower_name)
  validateLoanTextField('description', formData.value.description || '')
  const loanDate = formData.value.loan_date ? new Date(formData.value.loan_date) : null
  const dueDate = formData.value.due_date ? new Date(formData.value.due_date) : null
  const isDateValid = loanDate && dueDate && loanDate < dueDate
  return !!(
    !loanFieldErrors.value.borrower_name &&
    !loanFieldErrors.value.description &&
    formData.value.borrower_name?.trim() && 
    formData.value.amount && 
    Number(formData.value.amount) > 0 && 
    loanDate && dueDate &&
    formData.value.status &&
    isDateValid
  )
})

// Update isValidDailyExpensesForm to include description validation
const isValidDailyExpensesForm = computed(() => {
  if (activeTab.value !== 'dailyExpenses') return false
  validateDailyExpensesTextField('description', formData.value.description || '')
  return !!(
    !dailyExpensesFieldErrors.value.description &&
    formData.value.category &&
    (formData.value.category !== 'other' || (formData.value.category === 'other' && formData.value.custom_category?.trim())) &&
    formData.value.amount &&
    Number(formData.value.amount) > 0 &&
    formData.value.date
  )
})

// Update isValidEmployerPaymentForm
const isValidEmployerPaymentForm = computed(() => {
  if (activeTab.value !== 'employerPayments') return false
  validateEmployerPaymentTextField('notes', formData.value.notes || '')
  return (
    !employerPaymentFieldErrors.value.notes &&
    formData.value.cashier_id &&
    formData.value.salary_amount &&
    Number(formData.value.salary_amount) > 0 &&
    formData.value.payment_duration &&
    formData.value.payment_date &&
    formData.value.payment_method 
  )
})

const isFormValid = computed(() => {
  switch (activeTab.value) {
    case 'assets':
      return isValidAssetForm.value
    case 'investments':
      return isValidInvestmentForm.value
    case 'loans':
      return isValidLoanForm.value
    case 'dailyExpenses':
      return isValidDailyExpensesForm.value
    case 'supplierPayments':
      return false // No form for supplier payments
    case 'employerPayments':
      return isValidEmployerPaymentForm.value
    default:
      return false
  }
})

const isSubmitting = ref(false)

// Methods
const fetchData = async () => {
  isLoading.value = true
  try {
    // Fetch main data first (without supplier payments)
    const [assetsRes, investmentsRes, loansRes, expensesRes, employerPaymentsRes, cashiersRes] = await Promise.all([
      connection.get('/assets'),
      connection.get('/investments'),
      connection.get('/loans'),
      connection.get('/api/daily-expenses'),
      connection.get('/employer-payments'),
      connection.get('/cashiers') // Updated endpoint
    ])
    assets.value = assetsRes.data
    investments.value = investmentsRes.data
    loans.value = updateOverdueLoans(loansRes.data)
    dailyExpenses.value = expensesRes.data
    employerPayments.value = employerPaymentsRes.data?.data || []
    cashiers.value = cashiersRes.data?.data || [] // Updated to access data property

    // Fetch supplier payments in parallel, but don't block UI
    connection.get('/supplier-payments?fields=id,supplier_id,product_id,total_cost,remaining_balance,payment_status')
      .then(res => {
        supplierPayments.value = res.data
      })
      .catch(() => {
        supplierPayments.value = []
      })

    const overdueUpdates = loans.value
      .filter(loan => loan.status === 'overdue' && loansRes.data.find(l => l.id === loan.id)?.status === 'pending')
      .map(loan => connection.put(`/loans/${loan.id}`, { ...loan }))
    
    if (overdueUpdates.length > 0) {
      await Promise.all(overdueUpdates)
    }
  } catch (error) {
    console.error('Error fetching data:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: 'Failed to load or update data',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isLoading.value = false
  }
}

const setActiveTab = (tab) => {
  activeTab.value = tab
  resetForm()
}

const openAddForm = () => {
  formMode.value = 'add'
  resetForm()
  showForm.value = true
}

const openEditForm = (item) => {
  formMode.value = 'edit'
  currentItem.value = item
  
  if (activeTab.value === 'assets') {
    formData.value = {
      name: item.name,
      type: item.type,
      location: item.location,
      value: item.value
    }
  } else if (activeTab.value === 'investments') {
    formData.value = {
      investor_name: item.investor_name,
      amount: item.amount,
      investment_date: item.investment_date,
      description: item.description || ''
    }
  } else if (activeTab.value === 'loans') {
    formData.value = {
      borrower_name: item.borrower_name,
      amount: item.amount,
      loan_date: item.loan_date,
      due_date: item.due_date,
      status: item.status,
      description: item.description || ''
    }
  } else if (activeTab.value === 'dailyExpenses') {
    formData.value = {
      category: item.category,
      custom_category: item.custom_category || '',
      description: item.description || '',
      amount: item.amount,
      date: item.date
    }
  } else if (activeTab.value === 'employerPayments') {
    formData.value = {
      cashier_id: item.cashier_id || '',
      salary_amount: item.salary_amount || '',
      payment_duration: item.payment_duration || '',
      payment_date: item.payment_date || new Date().toISOString().split('T')[0],
      payment_method: item.payment_method || 'cash',
      notes: item.notes || ''
    }
  }
  
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
  resetForm()
}

const resetForm = () => {
  formData.value = {
    name: '',
    type: '',
    location: '',
    value: '',
    investor_name: '',
    amount: '',
    investment_date: '',
    borrower_name: '',
    loan_date: '',
    due_date: '',
    status: 'pending',
    description: '',
    category: '',
    custom_category: '',
    date: new Date().toISOString().split('T')[0],
    cashier_id: '',
    salary_amount: '',
    payment_duration: '',
    payment_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    notes: ''
  }
  currentItem.value = null
}

const printEmployerPaymentReceipt = () => {
  const element = document.getElementById('employer-payment-receipt-content')
  const opt = {
    margin: [10, 10, 10, 10],
    filename: `employer-payment-${employerPaymentReceiptData.value?.id || 'receipt'}.pdf`,
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { scale: 2, useCORS: true, logging: false },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  }
  html2pdf().set(opt).from(element).save()
}

const closeEmployerPaymentReceipt = () => {
  showEmployerPaymentReceipt.value = false
  employerPaymentReceiptData.value = null
}

const handleSubmit = async () => {
  if (!isFormValid.value || isSubmitting.value) return

  isSubmitting.value = true
  try {
    if (activeTab.value === 'employerPayments') {
      if (!isValidEmployerPaymentForm.value) {
        throw new Error('Please fill in all required employer payment fields correctly')
      }

      const paymentPayload = {
        cashier_id: formData.value.cashier_id,
        salary_amount: Number(formData.value.salary_amount),
        payment_duration: formData.value.payment_duration,
        payment_date: formData.value.payment_date,
        payment_method: formData.value.payment_method,
        notes: formData.value.notes || ''
      }

      if (formMode.value === 'add') {
        const response = await connection.post('/employer-payments', paymentPayload);
        if (response.data) {
          const newPayment = response.data.data;
          employerPayments.value.push(newPayment);
          employerPaymentReceiptData.value = {
            ...newPayment,
            cashier: cashiers.value.find(c => c.id === newPayment.cashier_id)
          }
          showEmployerPaymentReceipt.value = true
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Employer payment added successfully',
            background: '#1e293b',
            color: '#ffffff'
          });
          closeForm();
          return;
        }
      } else {
        const response = await connection.put(`/employer-payments/${currentItem.value.id}`, paymentPayload);
        if (response.data) {
          const updatedPayment = response.data.data;
          const idx = employerPayments.value.findIndex(p => p.id === currentItem.value.id);
          if (idx !== -1) employerPayments.value[idx] = updatedPayment;
          await Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: 'Employer payment updated successfully',
            background: '#1e293b',
            color: '#ffffff'
          });
          closeForm();
          return;
        }
      }
    }

    if (formMode.value === 'add') {
      if (activeTab.value === 'assets') {
        if (!isValidAssetForm.value) {
          throw new Error('Please fill in all required asset fields correctly')
        }
        const response = await connection.post('/assets', formData.value)
        assets.value.push(response.data)
      } else if (activeTab.value === 'investments') {
        if (!isValidInvestmentForm.value) {
          throw new Error('Please fill in all required investment fields correctly')
        }
        const response = await connection.post('/investments', formData.value)
        investments.value.push(response.data)
      } else if (activeTab.value === 'loans') {
        if (!isValidLoanForm.value) {
          throw new Error('Please fill in all required loan fields correctly')
        }
        const response = await connection.post('/loans', formData.value)
        loans.value.push(response.data)
      } else if (activeTab.value === 'dailyExpenses') {
        if (!isValidDailyExpensesForm.value) {
          throw new Error('Please fill in all required daily expenses fields correctly')
        }
        const response = await connection.post('/api/daily-expenses', formData.value)
        dailyExpenses.value.push(response.data)
      }

      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: `${activeTab.value.slice(0, -1)} added successfully`,
        background: '#1e293b',
        color: '#ffffff'
      })
    } else {
      if (activeTab.value === 'assets') {
        if (!isValidAssetForm.value) {
          throw new Error('Please fill in all required asset fields correctly')
        }
        const response = await connection.put(`/assets/${currentItem.value.id}`, formData.value)
        const index = assets.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) assets.value[index] = response.data
      } else if (activeTab.value === 'investments') {
        if (!isValidInvestmentForm.value) {
          throw new Error('Please fill in all required investment fields correctly')
        }
        const response = await connection.put(`/investments/${currentItem.value.id}`, formData.value)
        const index = investments.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) investments.value[index] = response.data
      } else if (activeTab.value === 'loans') {
        if (!isValidLoanForm.value) {
          throw new Error('Please fill in all required loan fields correctly')
        }
        const response = await connection.put(`/loans/${currentItem.value.id}`, formData.value)
        const index = loans.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) loans.value[index] = response.data
      } else if (activeTab.value === 'dailyExpenses') {
        if (!isValidDailyExpensesForm.value) {
          throw new Error('Please fill in all required daily expenses fields correctly')
        }
        const response = await connection.put(`/api/daily-expenses/${currentItem.value.id}`, formData.value) // Fixed endpoint
        const index = dailyExpenses.value.findIndex(item => item.id === currentItem.value.id)
        if (index !== -1) dailyExpenses.value[index] = response.data
      }
      
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: `${activeTab.value.slice(0, -1)} updated successfully`,
        background: '#1e293b',
        color: '#ffffff'
      })
    }
    closeForm()
    
  } catch (error) {
    console.error('Error submitting form:', error)
    Swal.fire({
      icon: 'error',
      title: 'Error!',
      text: error.message || 'Failed to save data. Please try again.',
      background: '#1e293b',
      color: '#ffffff'
    })
  } finally {
    isSubmitting.value = false
  }
}

const deleteItem = async (item) => {
  try {
    const result = await Swal.fire({
      title: `Delete ${activeTab.value.slice(0, -1)}?`,
      text: `Are you sure you want to delete ${item.name || item.investor_name || item.borrower_name || item.category || item.employee_name}? This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, delete it',
      background: '#1e293b',
      color: '#ffffff'
    });

    if (result.isConfirmed) {
      if (activeTab.value === 'assets') {
        await connection.delete(`/assets/${item.id}`);
        assets.value = assets.value.filter(asset => asset.id !== item.id);
      } else if (activeTab.value === 'investments') {
        await connection.delete(`/investments/${item.id}`);
        investments.value = investments.value.filter(investment => investment.id !== item.id);
      } else if (activeTab.value === 'loans') {
        await connection.delete(`/loans/${item.id}`);
        loans.value = loans.value.filter(loan => loan.id !== item.id);
      } else if (activeTab.value === 'dailyExpenses') {
        await connection.delete(`/api/daily-expenses/${item.id}`);
        dailyExpenses.value = dailyExpenses.value.filter(expense => expense.id !== item.id);
      } else if (activeTab.value === 'supplierPayments') {
        await connection.delete(`/supplier-payments/${item.id}`);
        supplierPayments.value = supplierPayments.value.filter(payment => payment.id !== item.id);
      } else if (activeTab.value === 'employerPayments') {
        await connection.delete(`/employer-payments/${item.id}`);
        employerPayments.value = employerPayments.value.filter(payment => payment.id !== item.id);
      }

      Swal.fire({
        title: 'Deleted!',
        text: `${item.name || item.investor_name || item.borrower_name || item.category || item.employee_name} has been deleted.`,
        icon: 'success',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      });
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Failed to delete the item. Please try again.',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    });
  }
};

const deleteSupplierPayment = async (item) => {
  try {
    const result = await Swal.fire({
      title: `Delete Supplier Payment?`,
      text: `Are you sure you want to delete this supplier payment? This action cannot be undone.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#EF4444',
      cancelButtonColor: '#6B7280',
      confirmButtonText: 'Yes, delete it',
      background: '#1e293b',
      color: '#ffffff'
    });

    if (result.isConfirmed) {
      await connection.delete(`/supplier-payments/${item.id}`)
      // Remove from UI immediately
      supplierPayments.value = supplierPayments.value.filter(payment => payment.id !== item.id)
      Swal.fire({
        title: 'Deleted!',
        text: `Supplier payment has been deleted.`,
        icon: 'success',
        confirmButtonColor: '#3B82F6',
        background: '#1e293b',
        color: '#ffffff'
      });
    }
  } catch (error) {
    Swal.fire({
      title: 'Error',
      text: 'Failed to delete the supplier payment. Please try again.',
      icon: 'error',
      confirmButtonColor: '#3B82F6',
      background: '#1e293b',
      color: '#ffffff'
    });
  }
};

const getActiveItems = () => {
  switch (activeTab.value) {
    case 'assets':
      return assets.value
    case 'investments':
      return investments.value
    case 'loans':
      return loans.value
    case 'dailyExpenses':
      return dailyExpenses.value
    case 'supplierPayments':
      return supplierPayments.value
    case 'employerPayments':
      return employerPayments.value
    default:
      return []
  }
}

const toggleSort = (field) => {
  if (sortField.value === field) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortField.value = field
    sortDirection.value = 'asc'
  }
}

const getSortIcon = (field) => {
  if (sortField.value !== field) return null
  return sortDirection.value === 'asc' ? ChevronUpIcon : ChevronDownIcon
}

const viewDetails = (item) => {
  viewingItem.value = item
  showViewModal.value = true
}

const updateOverdueLoans = (loansList) => {
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  
  return loansList.map(loan => {
    const dueDate = new Date(loan.due_date)
    dueDate.setHours(0, 0, 0, 0)
    
    if (loan.status === 'pending' && dueDate < today) {
      return { ...loan, status: 'overdue' }
    }
    return loan
  })
}

// Lifecycle hooks
onMounted(() => {
  fetchData()
  const intervalId = setInterval(() => {
    if (loans.value.length > 0) {
      loans.value = updateOverdueLoans(loans.value)
    }
  }, 3600000)
  
  onUnmounted(() => clearInterval(intervalId))
})

// Sidebar methods
const isSidebarVisible = ref(false)
const toggleSidebar = () => { isSidebarVisible.value = !isSidebarVisible.value }
const closeSidebar = () => { isSidebarVisible.value = false }
const showSidebar = () => { isSidebarVisible.value = true }

// Remove Supplier Payment Edit Modal state and methods
// const showSupplierPaymentEditModal = ref(false)
// const supplierPaymentEditData = ref({...})
// const isSupplierPaymentSubmitting = ref(false)
// const openSupplierPaymentEdit = (item) => {...}
// const closeSupplierPaymentEdit = () => {...}
// const handleSupplierPaymentUpdate = async () => {...}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900">
    <div class="fixed left-0 top-0 w-2 h-full z-[55] hover-trigger" @mouseenter="showSidebar"></div>
    <Sidebar :isVisible="isSidebarVisible" @closeSidebar="closeSidebar" />
    <Header @toggleSidebar="toggleSidebar" />

    <div class="w-full p-2 sm:p-4 lg:p-8 pt-24 lg:pt-28">
      <div class="financial-details-container h-full w-full bg-gradient-to-b from-slate-900 to-slate-800 text-white flex flex-col">
        <div class="px-2 sm:px-6 py-5 flex items-center justify-between border-b border-slate-700/50 backdrop-blur-sm bg-slate-900/80">
          <div class="flex items-center">
            <div class="bg-indigo-500/10 p-2.5 rounded-xl mr-3">
              <component :is="tabHeaderInfo.icon" class="w-6 h-6 text-indigo-400" />
            </div>
            <div>
              <h1 class="text-xl font-medium">
                {{ tabHeaderInfo.topic }} <span class="text-indigo-400 font-bold">Details</span>
              </h1>
              <p class="text-xs text-slate-400">{{ tabHeaderInfo.description }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-5">
            <div class="text-right">
              <div class="text-xs text-slate-400 mb-0.5">Total Value</div>
              <div class="text-lg font-bold text-white">{{ formatCurrency(totalValue) }}</div>
            </div>
            <button v-if="activeTab !== 'supplierPayments'" @click="openAddForm" 
                    class="p-2.5 rounded-full bg-indigo-600 text-white hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-600/20">
              <PlusIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <div class="px-2 sm:px-6 py-3 border-b border-slate-700/50 flex flex-wrap bg-slate-800/50 backdrop-blur-sm">
          <button @click="setActiveTab('assets')" 
                  class="px-4 py-2.5 rounded-lg flex items-center mr-2 transition-all duration-200"
                  :class="activeTab === 'assets' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <BuildingLibraryIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Assets</span>
          </button>
          <button @click="setActiveTab('investments')" 
                  class="px-4 py-2.5 rounded-lg flex items-center mr-2 transition-all duration-200"
                  :class="activeTab === 'investments' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <BanknotesIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Investments</span>
          </button>
          <button @click="setActiveTab('loans')" 
                  class="px-4 py-2.5 rounded-lg flex items-center mr-2 transition-all duration-200"
                  :class="activeTab === 'loans' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <ArrowsRightLeftIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Loans</span>
          </button>
          <button @click="setActiveTab('dailyExpenses')" 
                  class="px-4 py-2.5 rounded-lg flex items-center transition-all duration-200"
                  :class="activeTab === 'dailyExpenses' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <ArrowsRightLeftIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Daily Expenses</span>
          </button>
          <button @click="setActiveTab('supplierPayments')" 
                  class="px-4 py-2.5 rounded-lg flex items-center transition-all duration-200"
                  :class="activeTab === 'supplierPayments' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <BanknotesIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Supplier Payments</span>
          </button>
          <button @click="setActiveTab('employerPayments')" 
                  class="px-4 py-2.5 rounded-lg flex items-center transition-all duration-200"
                  :class="activeTab === 'employerPayments' ? 'bg-indigo-500/20 text-indigo-300 shadow-sm' : 'text-slate-400 hover:text-white hover:bg-slate-700/30'">
            <BanknotesIcon class="w-5 h-5 mr-2" />
            <span class="font-medium">Employer Payments</span>
          </button>
        </div>

        <div class="px-2 sm:px-6 py-4 border-b border-slate-700/50 bg-slate-800/30">
          <div v-if="activeTab !== 'employerPayments'" class="relative">
            <input v-model="searchQuery"
                   type="text" 
                   placeholder="Search..." 
                   class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl pl-11 pr-4 py-2.5 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
            <div class="absolute left-3.5 top-3 text-slate-500">
              <MagnifyingGlassIcon class="w-5 h-5" />
            </div>
          </div>
          <div v-else class="flex flex-col md:flex-row gap-3 items-center">
            <div class="flex gap-2 items-center">
              <label class="text-xs text-slate-400 font-medium mr-2">Search By:</label>
              <select v-model="employerPaymentSearchType"
                      class="bg-slate-800/80 border border-slate-600/50 rounded-xl px-1 py-3 text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 text-xs">
                <option value="name">Employer Name</option>
                <option value="payment_method">Payment Method</option>
                <option value="payment_date">Payment Date</option>
                <option value="payment_duration">Payment Duration</option>
              </select>
            </div>
            <div v-if="employerPaymentSearchType === 'name'" class="relative flex-1">
              <input v-model="employerPaymentSearchValue"
                     type="text"
                     placeholder="Search by employer name..."
                     class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl pl-11 pr-4 py-2.5 text-slate-200 placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
              <div class="absolute left-3.5 top-3 text-slate-500">
                <MagnifyingGlassIcon class="w-5 h-5" />
              </div>
            </div>
            <div v-else-if="employerPaymentSearchType === 'payment_method'" class="flex-1">
              <select v-model="employerPaymentSearchValue"
                      class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                <option value="">Select Payment Method</option>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="check">Check</option>
              </select>
            </div>
            <div v-else-if="employerPaymentSearchType === 'payment_date'" class="flex-1">
              <input v-model="employerPaymentSearchValue"
                     type="date"
                     class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
            </div>
            <div v-else-if="employerPaymentSearchType === 'payment_duration'" class="flex-1">
              <select v-model="employerPaymentSearchValue"
                      class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                <option value="">Select Duration</option>
                <option value="Daily">Daily</option>
                <option value="Weekly">Weekly</option>
                <option value="Monthly">Monthly</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
        </div>

        <div class="flex-1 overflow-auto">
          <div v-if="isLoading" class="flex flex-col items-center justify-center h-full">
            <div class="w-14 h-14 border-4 border-indigo-400/20 border-t-indigo-400 rounded-full animate-spin"></div>
            <div class="mt-4 text-slate-400 font-medium">Loading data...</div>
          </div>

          <div v-else-if="filteredItems.length === 0" class="flex flex-col items-center justify-center h-full text-center p-6">
            <div class="w-20 h-20 bg-slate-800/80 rounded-2xl flex items-center justify-center mb-5 border border-slate-700/50 shadow-xl">
              <component :is="activeTab === 'assets' ? BuildingLibraryIcon : 
                          activeTab === 'investments' ? BanknotesIcon : 
                          ArrowsRightLeftIcon" 
                        class="w-10 h-10 text-slate-500" />
            </div>
            <h3 class="text-xl font-medium text-slate-300 mb-2">No items found</h3>
            <p class="text-slate-500 max-w-md mb-8">
              {{ searchQuery ? 'Try adjusting your search query' : `You don't have any ${activeTab} yet` }}
            </p>
            <button 
              v-if="activeTab !== 'supplierPayments'" 
              @click="openAddForm" 
              class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-500 transition-all shadow-lg shadow-indigo-600/20 flex items-center">
              <PlusIcon class="w-5 h-5 mr-2" />
              <span>Add {{ activeTab.slice(0, -1) }}</span>
            </button>
          </div>
          <div v-else-if="activeTab === 'assets'" class="w-full overflow-x-auto">
            <table class="w-full min-w-[600px] border-collapse block sm:table">
              <thead class="block sm:table-header-group">
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30 block sm:table-row">
                  <th @click="toggleSort('name')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Name
                      <component v-if="getSortIcon('name')" :is="getSortIcon('name')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('type')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Type
                      <component v-if="getSortIcon('type')" :is="getSortIcon('type')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('location')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Location
                      <component v-if="getSortIcon('location')" :is="getSortIcon('location')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('value')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Value
                      <component v-if="getSortIcon('value')" :is="getSortIcon('value')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="block sm:table-row-group">
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors block sm:table-row">
                  <td class="px-6 py-4 text-white font-medium">{{ item.name }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.type }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.location }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.value) }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="activeTab === 'investments'" class="w-full overflow-x-auto">
            <table class="w-full min-w-[600px] border-collapse block sm:table">
              <thead class="block sm:table-header-group">
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30 block sm:table-row">
                  <th @click="toggleSort('investor_name')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Investor
                      <component v-if="getSortIcon('investor_name')" :is="getSortIcon('investor_name')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('amount')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Amount
                      <component v-if="getSortIcon('amount')" :is="getSortIcon('amount')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('investment_date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Date
                      <component v-if="getSortIcon('investment_date')" :is="getSortIcon('investment_date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('description')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Description
                      <component v-if="getSortIcon('description')" :is="getSortIcon('description')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="block sm:table-row-group">
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors block sm:table-row">
                  <td class="px-6 py-4 text-white font-medium">{{ item.investor_name }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.amount) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.investment_date) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.description }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="activeTab === 'loans'" class="w-full overflow-x-auto">
            <table class="w-full min-w-[700px] border-collapse block sm:table">
              <thead class="block sm:table-header-group">
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30 block sm:table-row">
                  <th @click="toggleSort('borrower_name')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Borrower
                      <component v-if="getSortIcon('borrower_name')" :is="getSortIcon('borrower_name')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('amount')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Amount
                      <component v-if="getSortIcon('amount')" :is="getSortIcon('amount')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('loan_date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Loan Date
                      <component v-if="getSortIcon('loan_date')" :is="getSortIcon('loan_date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('due_date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Due Date
                      <component v-if="getSortIcon('due_date')" :is="getSortIcon('due_date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('status')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Status
                      <component v-if="getSortIcon('status')" :is="getSortIcon('status')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="block sm:table-row-group">
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors block sm:table-row">
                  <td class="px-6 py-4 text-white font-medium">{{ item.borrower_name }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.amount) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.loan_date) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.due_date) }}</td>
                  <td class="px-6 py-4">
                    <span class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                          :class="{
                            'bg-amber-500/20 text-amber-400 border border-amber-500/20': item.status === 'pending',
                            'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': item.status === 'paid',
                            'bg-rose-500/20 text-rose-400 border border-rose-500/20': item.status === 'overdue'
                          }">
                      {{ item.status.charAt(0).toUpperCase() + item.status.slice(1) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="activeTab === 'dailyExpenses'" class="w-full overflow-x-auto">
            <table class="w-full min-w-[700px] border-collapse block sm:table">
              <thead class="block sm:table-header-group">
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30 block sm:table-row">
                  <th @click="toggleSort('category')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Category
                      <component v-if="getSortIcon('category')" :is="getSortIcon('category')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('custom_category')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Custom Category
                      <component v-if="getSortIcon('custom_category')" :is="getSortIcon('custom_category')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('description')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Description
                      <component v-if="getSortIcon('description')" :is="getSortIcon('description')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('amount')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Amount
                      <component v-if="getSortIcon('amount')" :is="getSortIcon('amount')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th @click="toggleSort('date')" 
                      class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase cursor-pointer hover:text-white">
                    <div class="flex items-center">
                      Date
                      <component v-if="getSortIcon('date')" :is="getSortIcon('date')" class="w-4 h-4 ml-1" />
                    </div>
                  </th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="block sm:table-row-group">
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors block sm:table-row">
                  <td class="px-6 py-4 text-white font-medium">{{ item.category }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.custom_category || '-' }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.description || '-' }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.amount) }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.date) }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="activeTab === 'supplierPayments'" class="w-full overflow-x-auto">
            <table class="w-full min-w-[700px] border-collapse block sm:table">
              <thead class="block sm:table-header-group">
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30 block sm:table-row">
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Supplier</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Product</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Total Cost</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Remaining</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Status</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="block sm:table-row-group">
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors block sm:table-row">
                  <td class="px-6 py-4 text-white font-medium">{{ item.supplier?.name || '-' }}</td>
                  <td class="px-6 py-4 text-slate-300">{{ item.product?.name || '-' }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.total_cost) }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.remaining_balance) }}</td>
                  <td class="px-6 py-4">
                    <span class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                          :class="{
                            'bg-amber-500/20 text-amber-400 border border-amber-500/20': item.payment_status === 'advance',
                            'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': item.payment_status === 'full'
                          }">
                      {{ item.payment_status.charAt(0).toUpperCase() + item.payment_status.slice(1) }}
                    </span>
                  </td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteSupplierPayment(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else-if="activeTab === 'employerPayments'" class="w-full overflow-x-auto">
            <table class="w-full min-w-[700px] border-collapse block sm:table">
              <thead class="block sm:table-header-group">
                <tr class="text-left border-b border-slate-700/50 bg-slate-800/30 block sm:table-row">
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Cashier Name</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Salary Amount</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Payment Duration</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Payment Date</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Payment Method</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Notes</th>
                  <th class="px-6 py-4 text-xs font-semibold text-slate-400 uppercase text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="block sm:table-row-group">
                <tr v-for="item in sortedItems" :key="item.id" class="border-b border-slate-700/30 hover:bg-slate-700/20 transition-colors block sm:table-row">
                  <td class="px-6 py-4 text-white font-medium">{{ item.cashier?.name || '-' }}</td>
                  <td class="px-6 py-4 text-slate-300 font-medium">{{ formatCurrency(item.salary_amount) }}</td>
                  <td class="px-6 py-4 text-slate-300">
                    <span
                      class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                      :class="{
                        'bg-blue-500/20 text-blue-400 border border-blue-500/30': item.payment_duration === 'Daily',
                        'bg-purple-500/20 text-purple-400 border border-purple-500/30': item.payment_duration === 'Weekly',
                        'bg-pink-500/20 text-pink-400 border border-pink-500/30': item.payment_duration === 'Monthly',
                        'bg-gray-500/20 text-gray-300 border border-gray-500/30': item.payment_duration === 'Other'
                      }"
                    >
                      {{
                        item.payment_duration === 'Daily'
                          ? 'Daily'
                          : item.payment_duration === 'Weekly'
                          ? 'Weekly'
                          : item.payment_duration === 'Monthly'
                          ? 'Monthly'
                          : item.payment_duration === 'Other'
                          ? 'Other'
                          : item.payment_duration
                      }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-slate-300">{{ formatDate(item.payment_date) }}</td>
                  <td class="px-6 py-4 text-slate-300">
                    <span
                      class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                      :class="{
                        'bg-indigo-500/20 text-indigo-400 border border-indigo-500/30': item.payment_method === 'bank_transfer',
                        'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30': item.payment_method === 'cash',
                        'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30': item.payment_method === 'check'
                      }"
                    >
                      {{
                        item.payment_method === 'bank_transfer'
                          ? 'Bank Transfer'
                          : item.payment_method === 'cash'
                          ? 'Cash'
                          : item.payment_method === 'check'
                          ? 'Check'
                          : item.payment_method
                      }}
                    </span>
                  </td>
                  <td class="px-6 py-4 text-slate-300">{{ item.notes || '-' }}</td>
                  <td class="px-6 py-4">
                    <div class="flex justify-end space-x-2">
                      <button @click="viewDetails(item)" class="text-cyan-400 hover:text-cyan-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <EyeIcon class="w-5 h-5" />
                      </button>
                      <button @click="openEditForm(item)" class="text-indigo-400 hover:text-indigo-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <PencilIcon class="w-5 h-5" />
                      </button>
                      <button @click="deleteItem(item)" class="text-rose-400 hover:text-rose-300 p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                        <TrashIcon class="w-5 h-5" />
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-sm">
          <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl border border-slate-700/50 shadow-2xl w-full max-w-lg mx-2 sm:mx-4 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/80">
              <h3 class="text-lg font-medium text-white flex items-center">
                <span class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center mr-3">
                  <component :is="activeTab === 'assets' ? BuildingLibraryIcon : 
                              activeTab === 'investments' ? BanknotesIcon : 
                              ArrowsRightLeftIcon" 
                            class="w-5 h-5 text-indigo-400" />
                </span>
                {{ formTitle }}
              </h3>
              <button @click="closeForm" class="text-slate-400 hover:text-white p-1.5 hover:bg-slate-700/50 rounded-lg transition-colors">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <form @submit.prevent="handleSubmit" class="p-6">
              <template v-if="activeTab === 'assets'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Name</label>
                  <input v-model="formData.name" type="text" required
                         @input="validateAssetTextField('name', formData.name)"
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                  <div v-if="assetFieldErrors.name" class="text-rose-400 text-xs mt-1">{{ assetFieldErrors.name }}</div>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Type</label>
                  <input v-model="formData.type" type="text" required
                         @input="validateAssetTextField('type', formData.type)"
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                  <div v-if="assetFieldErrors.type" class="text-rose-400 text-xs mt-1">{{ assetFieldErrors.type }}</div>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Location</label>
                  <input v-model="formData.location" type="text" required
                         @input="validateAssetTextField('location', formData.location)"
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                  <div v-if="assetFieldErrors.location" class="text-rose-400 text-xs mt-1">{{ assetFieldErrors.location }}</div>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Value</label>
                  <input v-model="formData.value" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div v-if="formData.value && Number(formData.value) <= 0" class="text-rose-400 text-sm mt-1">
                  Please enter a valid value greater than 0
                </div>
              </template>

              <template v-else-if="activeTab === 'investments'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Investor Name</label>
                  <input v-model="formData.investor_name" type="text" required
                         @input="validateInvestmentTextField('investor_name', formData.investor_name)"
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                  <div v-if="investmentFieldErrors.investor_name" class="text-rose-400 text-xs mt-1">{{ investmentFieldErrors.investor_name }}</div>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Amount</label>
                  <input v-model="formData.amount" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Investment Date</label>
                  <input v-model="formData.investment_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
                  <textarea v-model="formData.description" rows="3"
                            @input="validateInvestmentTextField('description', formData.description)"
                            class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all"></textarea>
                  <div v-if="investmentFieldErrors.description" class="text-rose-400 text-xs mt-1">{{ investmentFieldErrors.description }}</div>
                </div>
                <div v-if="formData.amount && Number(formData.amount) <= 0" class="text-rose-400 text-sm mt-1">
                  Please enter a valid amount greater than 0
                </div>
              </template>

              <template v-else-if="activeTab === 'loans'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Borrower Name</label>
                  <input v-model="formData.borrower_name" type="text" required
                         @input="validateLoanTextField('borrower_name', formData.borrower_name)"
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                  <div v-if="loanFieldErrors.borrower_name" class="text-rose-400 text-xs mt-1">{{ loanFieldErrors.borrower_name }}</div>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Amount</label>
                  <input v-model="formData.amount" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Loan Date</label>
                  <input v-model="formData.loan_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Due Date</label>
                  <input v-model="formData.due_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Status</label>
                  <select v-model="formData.status" required
                          class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                  </select>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
                  <textarea v-model="formData.description" rows="3"
                            @input="validateLoanTextField('description', formData.description)"
                            class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all"></textarea>
                  <div v-if="loanFieldErrors.description" class="text-rose-400 text-xs mt-1">{{ loanFieldErrors.description }}</div>
                </div>
                <div v-if="formData.loan_date && formData.due_date && new Date(formData.loan_date) >= new Date(formData.due_date)" 
                     class="text-rose-400 text-sm mt-1">
                  Loan date must be before due date
                </div>
              </template>

              <template v-else-if="activeTab === 'dailyExpenses'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Category</label>
                  <select v-model="formData.category" required
                          class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    <option value="">Select Category</option>
                    <option value="utility bills">Utility Bills</option>
                    <option value="transportation">Transportation</option>
                    <option value="rent">Rent</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div v-if="formData.category === 'other'" class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Custom Category</label>
                  <input v-model="formData.custom_category" type="text" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Description</label>
                  <textarea v-model="formData.description" rows="3"
                            @input="validateDailyExpensesTextField('description', formData.description)"
                            class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all"></textarea>
                  <div v-if="dailyExpensesFieldErrors.description" class="text-rose-400 text-xs mt-1">{{ dailyExpensesFieldErrors.description }}</div>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Amount</label>
                  <input v-model="formData.amount" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Date</label>
                  <input v-model="formData.date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div v-if="formData.amount && Number(formData.amount) <= 0" class="text-rose-400 text-sm mt-1">
                  Please enter a valid amount greater than 0
                </div>
              </template>

              <template v-else-if="activeTab === 'employerPayments'">
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Select Cashier</label>
                  <select v-model="formData.cashier_id" required
                          class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    <option value="">Select a cashier</option>
                    <option v-for="cashier in cashiers" :key="cashier.id" :value="cashier.id">
                      {{ cashier.name }}
                    </option>
                  </select>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Salary Amount</label>
                  <input v-model="formData.salary_amount" type="number" min="0" step="0.01" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Payment Duration</label>
                  <select v-model="formData.payment_duration" required
                          class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    <option value="">Select Duration</option>
                    <option value="Daily">Daily</option>
                    <option value="Weekly">Weekly</option>
                    <option value="Monthly">Monthly</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Payment Date</label>
                  <input v-model="formData.payment_date" type="date" required
                         class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all" />
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Payment Method</label>
                  <select v-model="formData.payment_method" required
                          class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="check">Check</option>
                  </select>
                </div>
                <div class="mb-5">
                  <label class="block text-sm font-medium text-slate-300 mb-1.5">Notes</label>
                  <textarea v-model="formData.notes" rows="3"
                            @input="validateEmployerPaymentTextField('notes', formData.notes)"
                            class="w-full bg-slate-800/80 border border-slate-600/50 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500/50 transition-all"></textarea>
                  <div v-if="employerPaymentFieldErrors.notes" class="text-rose-400 text-xs mt-1">{{ employerPaymentFieldErrors.notes }}</div>
                </div>
              </template>

              <div class="flex justify-end space-x-3 mt-6">
                <button type="button" @click="closeForm"
                        class="px-4 py-2.5 rounded-xl border border-slate-600/50 text-slate-300 hover:text-white hover:bg-slate-700 transition-all">
                  Cancel
                </button>
                <button type="submit"
                        :disabled="!isFormValid || isSubmitting"
                        :class="[
                          'px-5 py-2.5 rounded-xl font-medium transition-all shadow-lg',
                          isFormValid && !isSubmitting 
                            ? 'bg-indigo-600 text-white hover:bg-indigo-500 shadow-indigo-600/20' 
                            : 'bg-slate-600 text-slate-300 cursor-not-allowed'
                        ]">
                  <span v-if="isSubmitting" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Processing...
                  </span>
                  <span v-else>
                    {{ formMode === 'add' ? 'Add' : 'Update' }}
                  </span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <div v-if="showViewModal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-2 sm:p-4">
          <div class="bg-gradient-to-b from-slate-800 to-slate-900 rounded-2xl w-full max-w-xl p-4 sm:p-6 shadow-2xl border border-slate-700/50 max-h-[90vh] overflow-auto">
            <div class="flex justify-between items-center mb-6 border-b border-slate-700/50 pb-4">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/20 flex items-center justify-center">
                  <EyeIcon class="w-6 h-6 text-cyan-400" />
                </div>
                <h2 class="text-xl font-semibold text-white">
                  <span class="text-cyan-400">
                    {{ activeTab === 'employerPayments' ? 'Employer Payment Details' : activeTab.slice(0, -1) + ' Details' }}
                  </span>
                </h2>
              </div>
              <button @click="showViewModal = false"
                      class="text-slate-400 hover:text-slate-200 hover:bg-slate-700/50 p-2 rounded-lg transition-colors">
                <XMarkIcon class="w-5 h-5" />
              </button>
            </div>

            <div class="space-y-5" v-if="viewingItem">
              <template v-if="activeTab === 'assets'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.name }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Type</span>
                    <p class="text-white mt-1.5">{{ viewingItem.type }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Location</span>
                    <p class="text-white mt-1.5">{{ viewingItem.location }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Value</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.value) }}</p>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'investments'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Investor Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.investor_name }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Amount</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.amount) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Investment Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.investment_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Description</span>
                    <p class="text-white mt-1.5">{{ viewingItem.description || 'No description provided' }}</p>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'loans'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Borrower Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.borrower_name }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Amount</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.amount) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Loan Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.loan_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Due Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.due_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Status</span>
                    <p class="mt-1.5">
                      <span class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                            :class="{
                              'bg-amber-500/20 text-amber-400 border border-amber-500/20': viewingItem.status === 'pending',
                              'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': viewingItem.status === 'paid',
                              'bg-rose-500/20 text-rose-400 border border-rose-500/20': viewingItem.status === 'overdue'
                            }">
                        {{ viewingItem.status.charAt(0).toUpperCase() + viewingItem.status.slice(1) }}
                      </span>
                    </p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Description</span>
                    <p class="text-white mt-1.5">{{ viewingItem.description || 'No description provided' }}</p>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'dailyExpenses'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Category</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.category }}</p>
                  </div>
                  <div v-if="viewingItem.custom_category">
                    <span class="text-sm font-medium text-cyan-400">Custom Category</span>
                    <p class="text-white mt-1.5">{{ viewingItem.custom_category }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Description</span>
                    <p class="text-white mt-1.5">{{ viewingItem.description || 'No description provided' }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Amount</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.amount) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.date) }}</p>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'supplierPayments'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Supplier</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.supplier?.name || '-' }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Product</span>
                    <p class="text-white mt-1.5">{{ viewingItem.product?.name || '-' }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Total Cost</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.total_cost) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Remaining Balance</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.remaining_balance) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Status</span>
                    <p class="mt-1.5">
                      <span class="px-3 py-1.5 rounded-full text-xs font-medium inline-block"
                            :class="{
                              'bg-amber-500/20 text-amber-400 border border-amber-500/20': viewingItem.payment_status === 'advance',
                              'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': viewingItem.payment_status === 'full'
                            }">
                        {{ viewingItem.payment_status.charAt(0).toUpperCase() + viewingItem.payment_status.slice(1) }}
                      </span>
                    </p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Transactions</span>
                    <table class="w-full mt-2 text-sm">
                      <thead>
                        <tr>
                          <th class="text-left text-slate-400 font-semibold py-1">Amount Paid</th>
                          <th class="text-left text-slate-400 font-semibold py-1">Method</th>
                          <th class="text-left text-slate-400 font-semibold py-1">Check #</th>
                          <th class="text-left text-slate-400 font-semibold py-1">Bank</th>
                          <th class="text-left text-slate-400 font-semibold py-1">Paid At</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="tx in viewingItem.transactions" :key="tx.id">
                          <td class="py-1 text-white">{{ formatCurrency(tx.amount_paid) }}</td>
                          <td class="py-1 text-white">{{ tx.payment_method }}</td>
                          <td class="py-1 text-white">{{ tx.check_number || '-' }}</td>
                          <td class="py-1 text-white">{{ tx.bank_name || '-' }}</td>
                          <td class="py-1 text-white">{{ formatDate(tx.paid_at) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </template>

              <template v-if="activeTab === 'employerPayments'">
                <div class="bg-slate-800/50 backdrop-blur-sm p-5 rounded-xl border border-slate-700/30 space-y-4">
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Cashier Name</span>
                    <p class="text-white mt-1.5 font-medium">{{ viewingItem.cashier?.name || '-' }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Salary Amount</span>
                    <p class="text-white mt-1.5 font-medium">{{ formatCurrency(viewingItem.salary_amount) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Payment Duration</span>
                    <p class="text-white mt-1.5">{{ viewingItem.payment_duration }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Payment Date</span>
                    <p class="text-white mt-1.5">{{ formatDate(viewingItem.payment_date) }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Payment Method</span>
                    <p class="text-white mt-1.5">{{ viewingItem.payment_method }}</p>
                  </div>
                  <div>
                    <span class="text-sm font-medium text-cyan-400">Notes</span>
                    <p class="text-white mt-1.5">{{ viewingItem.notes || 'No notes provided' }}</p>
                  </div>
                </div>
              </template>
            </div>

            <div class="flex justify-end mt-6 pt-4 border-t border-slate-700/50">
              <button @click="showViewModal = false"
                      class="px-5 py-2.5 bg-slate-700 text-white rounded-xl hover:bg-slate-600 transition-all shadow-lg flex items-center">
                <XMarkIcon class="w-5 h-5 mr-2" />
                Close
              </button>
            </div>
          </div>
        </div>

        <div v-if="showEmployerPaymentReceipt" class="fixed inset-0 z-50 flex items-center justify-center p-2 sm:p-4">
          <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closeEmployerPaymentReceipt"></div>
          <div class="relative bg-white rounded-lg shadow-xl w-full max-w-[500px] z-10 animate-scale-in overflow-auto max-h-[90vh]">
            <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
              <div class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <BanknotesIcon class="w-6 h-6 text-indigo-500" />
                Employer Payment Receipt
              </div>
              <button @click="closeEmployerPaymentReceipt" class="p-2 hover:bg-gray-200 rounded-full transition-colors">
                <XMarkIcon class="w-5 h-5 text-gray-500" />
              </button>
            </div>
            <div id="employer-payment-receipt-content" class="p-6 text-gray-800">
              <div class="mb-4">
                <div class="text-2xl font-bold text-indigo-600">Weads Horana Pvt Ltd</div>
                <div class="text-sm text-gray-600 mt-1">
                  102 Railway Ave.<br>
                  Kandy, Sri Lanka<br>
                  Phone: (94) 81 123 4567<br>
                  Email: info@hardwaresupply.com
                </div>
              </div>
              <div class="mb-4">
                <div class="text-base font-bold text-gray-800">Receipt #: {{ employerPaymentReceiptData?.id }}</div>
                <div class="text-sm text-gray-600">Date: <span class="text-gray-800">{{ formatDate(employerPaymentReceiptData?.payment_date) }}</span></div>
              </div>
              <div class="mb-4">
                <div class="text-sm text-gray-600 uppercase tracking-wider mb-1">Cashier</div>
                <div class="text-base font-bold text-gray-800">{{ employerPaymentReceiptData?.cashier?.name || '-' }}</div>
              </div>
              <div class="mb-4">
                <div class="text-sm text-gray-600 uppercase tracking-wider mb-1">Payment Details</div>
                <div class="space-y-1 text-sm">
                  <div class="flex justify-between">
                    <span class="text-gray-600">Salary Amount:</span>
                    <span class="text-indigo-600 font-bold">{{ formatCurrency(employerPaymentReceiptData?.salary_amount) }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Payment Duration:</span>
                    <span class="text-gray-800">{{ employerPaymentReceiptData?.payment_duration }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Payment Method:</span>
                    <span class="text-gray-800">{{ employerPaymentReceiptData?.payment_method }}</span>
                  </div>
                  <div class="flex justify-between">
                    <span class="text-gray-600">Notes:</span>
                    <span class="text-gray-800">{{ employerPaymentReceiptData?.notes || '-' }}</span>
                  </div>
                </div>
              </div>
              <div class="mt-8 text-xs text-gray-500">
                <div class="font-medium text-gray-600 mb-1">Terms & Conditions:</div>
                <ul class="list-disc pl-4 space-y-1">
                  <li>Payment is final and non-refundable</li>
                  <li>All prices are in Sri Lankan Rupees (Rs.)</li>
                </ul>
              </div>
            </div>
            <div class="sticky bottom-0 bg-white p-4 border-t border-gray-200">
              <button @click="printEmployerPaymentReceipt"
                class="w-full py-2.5 bg-indigo-500 hover:bg-indigo-600 text-white rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
                <BanknotesIcon class="w-5 h-5" />
                <span>Download PDF</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.financial-details-container {
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
}

/* Responsive table styles */
@media (max-width: 640px) {
  .financial-details-container table {
    font-size: 0.85rem;
  }
  .financial-details-container th,
  .financial-details-container td {
    padding-left: 0.5rem !important;
    padding-right: 0.5rem !important;
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
  }
}

::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(15, 23, 42, 0.8);
}

::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.3);
  border-radius: 8px;
}

::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.5);
}

* {
  transition: background-color 0.2s, border-color 0.2s, color 0.2s, box-shadow 0.2s;
}

.hover-trigger {
  background: transparent;
}
</style>
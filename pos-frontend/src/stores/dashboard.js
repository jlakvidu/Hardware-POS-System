import { defineStore } from 'pinia'
import { ref } from 'vue'
import { connection } from '@/api/axios'

export const useDashboardStore = defineStore('dashboard', {
  state: () => ({
    dashboardStats: {
      weeklyEarnings: 0,
      totalSales: 0,
      totalCustomers: 0,
      totalSuppliers: 0,
      totalProducts: 0,
      totalEmployees: 0,
      salesGrowth: '+4.75%',
      customerGrowth: '+2.85%',
      supplierGrowth: '+3.15%',
      productGrowth: '+4.45%',
      employeeGrowth: '0%',
      employeeStatus: 'Loading...'
    },
    chartData: {
      labels: ["CASH", "CREDIT_CARD", "DEBIT_CARD"],
      datasets: [{
        data: [0, 0, 0],
        backgroundColor: [
          "rgba(34, 197, 94, 0.9)",
          "rgba(56, 189, 248, 0.9)",
          "rgba(168, 85, 247, 0.9)",
        ],
        borderColor: [
          "rgba(22, 163, 74, 1)",
          "rgba(14, 165, 233, 1)", 
          "rgba(147, 51, 234, 1)",
        ],
        borderWidth: 1,
      }]
    },
    recentTransactions: [],
    isLoading: false,
    lastFetchTime: 0
  }),
  actions: {
    async fetchDashboardData(force = false) {
      this.isLoading = true
      try {
        // Add cache-busting param to avoid browser cache
        const cacheBust = `?t=${Date.now()}`
        const [salesResponse, productCount, employeeCount, salesData, paymentDistribution] = await Promise.all([
          connection.get(`/reports/sales/today${cacheBust}`),
          connection.get(`/products${cacheBust}`),
          connection.get(`/cashiers${cacheBust}`),
          connection.get(`/sales${cacheBust}`),
          connection.get(`/reports/sales/payment${cacheBust}`)
        ])

        if (paymentDistribution.data?.payment_distribution) {
          const distributions = {
            CASH: 0,
            CREDIT_CARD: 0,
            DEBIT_CARD: 0
          }
          paymentDistribution.data.payment_distribution.forEach(item => {
            if (distributions.hasOwnProperty(item.payment_type)) {
              distributions[item.payment_type] = item.total
            }
          })
          // Force reactivity for chartData by replacing the object
          this.chartData = {
            ...this.chartData,
            datasets: [{
              ...this.chartData.datasets[0],
              data: [
                distributions.CASH,
                distributions.CREDIT_CARD,
                distributions.DEBIT_CARD
              ]
            }]
          }
        }

        const allTransactions = salesData.data?.data || []
        this.recentTransactions = allTransactions
          .reverse()
          .slice(0, 5)
          .map(sale => ({
            id: sale.id,
            product: `${sale.product_sales?.[0]?.product?.name || 'Unknown Product'} x${sale.product_sales?.[0]?.quantity || 1}`,
            date: new Date(sale.created_at).toLocaleString('en-US', {
              year: 'numeric',
              month: '2-digit',
              day: '2-digit',
              hour: '2-digit',
              minute: '2-digit'
            }),
            method: sale.payment_type,
            amount: sale.amount,
            status: sale.status
          }))

        this.dashboardStats = {
          weeklyEarnings: salesResponse.data.total_income || 0,
          totalSales: salesResponse.data.total_sales || 0,
          totalCustomers: salesResponse.data.total_customers || 0,
          totalSuppliers: salesResponse.data.total_suppliers || 0,
          totalProducts: productCount.data?.data?.length || 0,
          totalEmployees: employeeCount.data?.data?.length || 0,
          salesGrowth: '+4.75%',
          customerGrowth: '+2.85%',
          supplierGrowth: '+3.15%',
          productGrowth: '+4.45%',
          employeeGrowth: employeeCount.data?.data?.length > 0 ? `+${((employeeCount.data.data.length/10) * 100).toFixed(2)}%` : '0%',
          employeeStatus: employeeCount.data?.data?.length > 0 ? 'Active' : 'No Employees'
        }

        this.lastFetchTime = Date.now()
      } catch (error) {
        console.error('Error fetching dashboard data:', error)
        this.dashboardStats.employeeStatus = 'Error loading data'
      } finally {
        this.isLoading = false
      }
    }
  }
})

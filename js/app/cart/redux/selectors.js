import { createSelector } from 'reselect'
import _ from 'lodash'

import { totalTaxExcluded } from '../../utils/tax'

export const selectIsDeliveryEnabled = createSelector(
  state => state.cart.restaurant,
  (restaurant) => {
    if (restaurant && Array.isArray(restaurant.fulfillmentMethods)) {
      return _.includes(restaurant.fulfillmentMethods, 'delivery')
    }
    return true
  }
)

export const selectIsCollectionEnabled = createSelector(
  state => state.cart.restaurant,
  (restaurant) => {
    if (restaurant && Array.isArray(restaurant.fulfillmentMethods)) {
      return _.includes(restaurant.fulfillmentMethods, 'collection')
    }
    return false
  }
)

export const selectIsSameRestaurant = createSelector(
  state => state.cart,
  state => state.restaurant,
  (cart, restaurant) => {
    if (cart.restaurant) {
      return cart.restaurant.id === restaurant.id
    }
    return false
  }
)

export const selectItems = createSelector(
  state => state.cart.items,
  selectIsSameRestaurant,
  (items, isSameRestaurant) => isSameRestaurant ? items : []
)

export const selectShowPricesTaxExcluded = createSelector(
  state => state.country,
  (country) => country === 'ca'
)

export const selectItemsTotal = createSelector(
  state => state.cart.items,
  state => state.cart.itemsTotal,
  selectIsSameRestaurant,
  selectShowPricesTaxExcluded,
  (items, itemsTotal, isSameRestaurant, showPricesTaxExcluded) => {

    if (!isSameRestaurant) {
      return 0
    }

    if (showPricesTaxExcluded) {
      return _.reduce(items, (sum, item) => {
        return sum + totalTaxExcluded(item)
      }, 0)
    }

    return itemsTotal
  }
)

export const selectVariableCustomerAmountEnabled = createSelector(
  state => state.cart,
  (cart) => {
    if (cart.restaurant) {
      return cart.restaurant.variableCustomerAmountEnabled
    }

    return false
  }
)

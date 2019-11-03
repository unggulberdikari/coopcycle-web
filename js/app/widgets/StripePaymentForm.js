import React, { useState } from 'react'
import { render } from 'react-dom'
import _ from 'lodash'
import classNames from 'classnames'

import mastercard from 'payment-icons/min/flat/mastercard.svg'
import visa from 'payment-icons/min/flat/visa.svg'
import giropay from '../../../assets/svg/giropay.svg'

const style = {
  base: {
    color: '#32325d',
    lineHeight: '18px',
    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
    fontSmoothing: 'antialiased',
    fontSize: '16px',
    '::placeholder': {
      color: '#aab7c4'
    }
  },
  invalid: {
    color: '#fa755a',
    iconColor: '#fa755a'
  }
}

function disableBtn(btn) {
  btn.setAttribute('disabled', '')
  btn.disabled = true
}

function enableBtn(btn) {
  btn.disabled = false
  btn.removeAttribute('disabled')
}

const methodPickerStyles = {
  display: 'flex',
  flexDirection: 'row',
  alignItems: 'center',
  justifyContent: 'space-between',
}

const methodPickerBtnClassNames = {
  'btn': true,
  'btn-default': true,
  'p-2': true
}

const PaymentMethodPicker = ({ methods, onSelect }) => {

  const [ method, setMethod ] = useState('')

  return (
    <div style={ methodPickerStyles }>
      <button type="button" className={ classNames({ ...methodPickerBtnClassNames, active: method === 'card' }) }
        onClick={ () => { setMethod('card'); onSelect('card') } }>
        <img src={ visa } height="45" className="mr-2" />
        <img src={ mastercard } height="45" />
      </button>
      { _.includes(methods, 'giropay') && (
        <button type="button"  className={ classNames({ ...methodPickerBtnClassNames, active: method === 'giropay' }) }
          onClick={ () => { setMethod('giropay'); onSelect('giropay') } }>
          <img src={ giropay } height="45" />
        </button>
      )}
    </div>
  )
}

let errorElement

function handleError(result) {

  $('.btn-payment').removeClass('btn-payment__loading')
  $('.btn-payment').attr('disabled', false)

  errorElement.textContent = result.error.message
  errorElement.classList.remove('hidden')
}

function handleServerResponse(response, stripe, form, tokenElement) {

  if (response.error) {
    handleError(response)
  } else if (response.requires_action) {

    // Use Stripe.js to handle required card action
    stripe.handleCardAction(
      response.payment_intent_client_secret
    ).then(function(result) {
      if (result.error) {
        handleError(result)
      } else {
        tokenElement.setAttribute('value', result.paymentIntent.id)
        form.submit()
      }
    })

  } else {
    tokenElement.setAttribute('value', response.payment_intent)
    form.submit()
  }
}

// @see https://stripe.com/docs/payments/payment-intents/web-manual
export default function(form, options) {

  const submitButton = form.querySelector('input[type="submit"],button[type="submit"]')

  const methods = Array
    .from(form.querySelectorAll('input[name="checkout_payment[method]"]'))
    .map((el) => el.value)

  disableBtn(submitButton)

  errorElement = document.getElementById('card-errors')

  const confirmPaymentRoute = options.confirmPaymentRoute || window.Routing.generate('order_confirm_payment')

  let stripeOptions = {}

  if (options.account) {
    stripeOptions = {
      ...stripeOptions,
      stripeAccount: options.account
    }
  }

  // @see https://stripe.com/docs/payments/payment-methods/connect#creating-paymentmethods-directly-on-the-connected-account
  const stripe = Stripe(options.publishableKey, stripeOptions)

  // TODO Check options are ok

  var elements = stripe.elements()

  const card = elements.create('card', { style, hidePostalCode: true })

  card.addEventListener('change', function(event) {
    const displayError = document.getElementById('card-errors')
    if (event.error) {
      displayError.textContent = event.error.message
    } else {
      displayError.textContent = ''
    }
  })

  card.on('ready', function() {
    enableBtn(submitButton)
  })

  form.addEventListener('submit', function(event) {

    if (methods.length > 1 && form.querySelector('input[name="checkout_payment[method]"]:checked').value !== 'card') {
      return
    }

    event.preventDefault()

    $('.btn-payment').addClass('btn-payment__loading')
    disableBtn(submitButton)

    errorElement.classList.add('hidden')

    stripe.createPaymentMethod('card', card, {
      billing_details: {
        name: options.cardholderNameElement.value
      }
    }).then(function(result) {

      if (result.error) {
        handleError(result)
      } else {
        fetch(confirmPaymentRoute, {
          method: 'POST',
          credentials: 'include',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ payment_method_id: result.paymentMethod.id })
        }).then(function(result) {
          result.json().then(function(json) {
            handleServerResponse(json, stripe, form, options.tokenElement)
          })
        })
      }
    })
  })

  const onSelect = value => {
    form.querySelector(`input[name="checkout_payment[method]"][value="${value}"]`).checked = true
    if (value === 'card') {
      card.mount('#card-element')
      document.getElementById('payment-redirect-help').classList.add('hidden')
    } else {
      card.unmount()
      document.getElementById('card-errors').textContent = ''
      document.getElementById('payment-redirect-help').classList.remove('hidden')
      enableBtn(submitButton)
    }
  }

  if (methods.length > 1) {

    // Replace radio buttons

    document
      .querySelectorAll('#checkout_payment_method .radio')
      .forEach(el => el.classList.add('hidden'))

    const el = document.createElement('div')
    document.querySelector('#checkout_payment_method').appendChild(el)

    render(
      <PaymentMethodPicker methods={ methods } onSelect={ onSelect } />,
      el
    )

  } else {
    card.mount('#card-element')
  }
}

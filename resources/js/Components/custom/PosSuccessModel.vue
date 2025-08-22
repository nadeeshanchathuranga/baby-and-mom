<template>
   <TransitionRoot as="template" :show="open" static>
      <Dialog class="relative z-10" static>
         <!-- Modal Overlay -->
         <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0" enter-to="opacity-100"
            leave="ease-in duration-200" leave-from="opacity-100" leave-to="opacity-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click.stop />
         </TransitionChild>
         <!-- Modal Content -->
         <div class="fixed inset-0 z-10 flex items-center justify-center">
            <TransitionChild as="template" enter="ease-out duration-300" enter-from="opacity-0 scale-95"
               enter-to="opacity-100 scale-100" leave="ease-in duration-200" leave-from="opacity-100 scale-100"
               leave-to="opacity-0 scale-95">
               <DialogPanel
                  class="bg-white border-4 border-blue-600 rounded-[20px] shadow-xl max-w-xl w-full p-6 text-center">
                  <!-- Modal Title -->
                  <DialogTitle class="text-5xl font-bold">Payment Successful!</DialogTitle>
                  <div class="w-full h-full flex flex-col justify-center items-center space-y-8 mt-4">
                     <p class="text-justify text-3xl text-black">
                        Order Payment is Successful!
                     </p>
                     <div>
                        <img src="/images/checked.png" class="h-24 object-cover w-full" />
                     </div>
                  </div>
                  <div class="flex justify-center items-center space-x-4 pt-4 mt-4">
                     <!-- <p @click="handlegGuideReceipt"
                        class="cursor-pointer bg-amber-600 text-white font-bold uppercase tracking-wider px-4 shadow-xl   py-4 rounded-xl">
                    Guid Reciept
                     </p> -->
                     <p @click="handlePrintReceipt"
                        class="cursor-pointer bg-blue-600 text-white font-bold uppercase tracking-wider px-4 shadow-xl   py-4 rounded-xl">
                        Print Receipt
                     </p>
                     <p @click="$emit('update:open', false)"
                        class="cursor-pointer bg-red-600 text-white font-bold uppercase tracking-wider px-4 shadow-xl   py-4 rounded-xl">
                        Close
                     </p>
                  </div>
               </DialogPanel>
            </TransitionChild>
         </div>
      </Dialog>
   </TransitionRoot>
</template>
<script setup>
   import {
       Dialog,
       DialogPanel,
       DialogTitle,
       TransitionChild,
       TransitionRoot,
   } from "@headlessui/vue";
   import { computed } from "vue";
   import { ref } from "vue";
   import { useForm, usePage } from "@inertiajs/vue3";

   const page = usePage();

   // Access the companyInfo from the page props
   const companyInfo = computed(() => page.props.companyInfo);

   if (companyInfo.value) {
       console.log(companyInfo.value);
   } else {
       console.log('companyInfo is undefined or null');
   }

   const handleClose = () => {
       console.log("Modal close prevented");
   };

   const emit = defineEmits(["update:open"]);

   // The `open` prop controls the visibility of the modal    ${companyInfo?.value?.name ? `<h1>${companyInfo.value.name}</h1>` : ''}
   const props = defineProps({
       open: {
           type: Boolean,
           required: true,
       },
       products: {
           type: Array,
           required: true,
       },
       cashier: Object,
       customer: Object,
       orderid: String,
       balance: Number,
       cash: Number,
       subTotal: String,
       totalDiscount: String,
       total: String,
       custom_discount: Number,
       custom_discount_type: String,
       paymentMethod: String,
       isWholesale: Boolean,
       credit_bill: Boolean,
       guide_comi: [Number, String],
       guide_cash: [Number, String],
       guide_name: String,
       guide_pending: [Boolean, Number],
   });

   const handlePrintReceipt = () => {
       // Calculate totals from props.products
       const subTotal = props.products.reduce(
           (sum, product) =>
               sum + parseFloat(product.selling_price) * product.quantity,
           0
       );
       const customDiscount = Number(props.custom_discount || 0);
       const totalDiscount = props.products
           .reduce((total, item) => {
               // Check if item has a discount
               if (item.discount && item.discount > 0 && item.apply_discount == true) {
                   const discountAmount =
                       (parseFloat(item.selling_price) - parseFloat(item.discounted_price)) *
                       item.quantity;
                   return total + discountAmount;
               }
               return total; // If no discount, return total as-is
           }, 0)
           .toFixed(2); // Ensures two decimal places

       const discount = 0; // Example discount (can be dynamic)
       const total = subTotal - totalDiscount - customDiscount;

       // Generate table rows dynamically using props.products
       const productRows = props.products
           .map((product) => {
               // Determine the price based on discount
               const price = product.discount > 0 && product.apply_discount
                   ? product.discounted_price  // Use discounted price if discount is applied
                   : product.selling_price;    // Use selling price if no discount

               return `
           <tr>
             <td>${product.name}</td>
             <td style="text-align: center;">${product.quantity}</td>
             <td>
               ${product.discount > 0 && product.apply_discount
                       ? `<div style="font-weight: bold; font-size: 7px; background-color:black; color:white;text-align:center;">${product.discount}% off</div>`
                       : ""
                   }
               <div>${product.selling_price}</div>
             </td>
           </tr>
         `;
           })
           .join("");


       // Generate the receipt HTML



const receiptHTML = `
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Receipt</title>
        <style>
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                }
            }
            body {
                background-color: #ffffff;
                font-size: 12px;
                font-family: "Arial", sans-serif;
                margin: 0;
                padding: 10px;
                color: #000;
            }
            .header {

                margin-bottom: 16px;
            }
            .header h1 {
                font-size: 20px;
                font-weight: bold;
                margin: 0;
            }
            .header p {
                font-size: 10px;
                margin: 4px 0;
            }
            .section {
                margin-bottom: 16px;
                padding-top: 8px;
                border-top: 1px solid #000;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                font-size: 12px;
                margin-top: 8px;
            }
            .info-row p {
                margin: 0;
                font-weight: bold;
            }
            .info-row small {
                font-weight: normal;
            }
            table {
                width: 100%;
                font-size: 12px;
                border-collapse: collapse;
                margin-top: 8px;
            }
            table th,
            table td {
                padding: 6px 8px;
            }
            table th {
                text-align: left;
            }
            table td {
                text-align: right;
            }
            table td:first-child {
                text-align: left;
            }
            .totals {
                border-top: 1px solid #000;
                padding-top: 8px;
                font-size: 12px;
            }
            .totals div {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .totals div:nth-child(4) {
                font-size: 14px;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                font-size: 10px;
                margin-top: 16px;
            }
            .footer p {
                margin: 6px 0;
            }
            .footer .italic {
                font-style: italic;
            }
        </style>
    </head>
    <body>

       <div class="receipt-container">
<div class="header">
   <img src="/images/billlogo.png" style="width: 250px; height:110px;" />
   ${companyInfo?.value?.name ? `
   <h1>${companyInfo.value.name}</h1>
   ` : ''}
   ${companyInfo?.value?.address ? `
   <p>${companyInfo.value.address}</p>
   ` : ''}
   ${(companyInfo?.value?.phone || companyInfo?.value?.phone2 || companyInfo?.value?.email)
   ? `
   <p>${companyInfo.value.phone || ''} | ${companyInfo.value.phone2 || ''}  ${companyInfo.value.email || ''}</p>
   `
   : ''}
</div>
<div class="info-row">
   <div>
      <p>Date:</p>
      <small>${new Date().toLocaleDateString()} </small>
   </div>
   <div>
      <p>Order No:</p>
      <small>${props.orderid}</small>
   </div>
</div>
<div class="info-row">
   <div>
      <p>Customer:</p>
      <small>${props.customer.name}</small>
   </div>
   <div>
      <p>Cashier:</p>
      <small>${props.cashier.name}</small>
   </div>
</div>
<div class="info-row">
   <p>Billing Type: <small>${props.isWholesale ? 'Wholesale' : 'Retail'}</small></p>
   ${props.paymentMethod === 'online' ? `
   <p>Payment Method: <small>Cheque</small></p>
   ` : ''}
   <p>Credit Bill: <small>${props.credit_bill ? 'Yes' : 'No'}</small></p>
</div>
<div class="section">
   <table>
      <thead>
         <tr>
            <th>Description</th>
            <th style="text-align: center;">Qty</th>
            <th style="text-align: right;">Price</th>
         </tr>
      </thead>
      <tbody>
         ${productRows}
      </tbody>
   </table>
</div>
<div class="totals">
   <div>
      <span>Sub Total</span>
      <span>${(Number(props.subTotal) || 0).toFixed(2)} LKR</span>
   </div>
   <div>
      <span>Discount</span>
      <span>${(Number(props.totalDiscount) || 0).toFixed(2)} LKR</span>
   </div>
   <div>
      <span>Custom Discount</span>
      <span>
      ${(Number(props.custom_discount) || 0).toFixed(2)} ${props.custom_discount_type === 'percent' ? '%' : props.custom_discount_type === 'fixed' ? 'LKR' : ''}
      </span>
   </div>
   <div>
      <span>Total</span>
      <span>${(Number(props.total) || 0).toFixed(2)} LKR</span>
   </div>
   <div>
      <span>Cash</span>
      <span>${(Number(props.cash) || 0).toFixed(2)} LKR</span>
   </div>
   <div style="font-weight: bold;">
      <span>Balance</span>
      <span>${(Number(props.balance) || 0).toFixed(2)} LKR</span>
   </div>
</div>
<div class="footer">
   <p>THANK YOU COME AGAIN</p>
   <p class="italic">Let the quality define its own standards</p>
   <p style="font-weight: bold;">Powered by JAAN Network Ltd.</p>
   <p>${new Date().toLocaleTimeString()}</p>
</div>





    </body>
</html>
`;









       // Open a new window
       const printWindow = window.open("", "_blank");
       if (!printWindow) {
           alert("Failed to open print window. Please check your browser settings.");
           return;
       }

       // Write the content to the new window
       printWindow.document.open();
       printWindow.document.write(receiptHTML);
       printWindow.document.close();

       // Wait for the content to load before triggering print
       printWindow.onload = () => {
           printWindow.focus();
           printWindow.print();
           printWindow.close();
       };
   };

   const getGuideStatus = () => {
    console.log('Guide pending value:', props.guide_pending);
console.log('Guide pending type:', typeof props.guide_pending);
    const pending = props.guide_pending;
    // Handle different data types
    if (pending === true || pending === 1 || pending === '1') {
        return 'Pending';
    }
    return 'Completed';
};




   const handlegGuideReceipt = () => {
       // Calculate totals from props.products
       const subTotal = props.products.reduce(
           (sum, product) =>
               sum + parseFloat(product.selling_price) * product.quantity,
           0
       );
       const customDiscount = Number(props.custom_discount || 0);
       const totalDiscount = props.products
           .reduce((total, item) => {
               // Check if item has a discount
               if (item.discount && item.discount > 0 && item.apply_discount == true) {
                   const discountAmount =
                       (parseFloat(item.selling_price) - parseFloat(item.discounted_price)) *
                       item.quantity;
                   return total + discountAmount;
               }
               return total; // If no discount, return total as-is
           }, 0)
           .toFixed(2); // Ensures two decimal places

       const discount = 0; // Example discount (can be dynamic)
       const total = subTotal - totalDiscount - customDiscount;

       // Generate table rows dynamically using props.products
       const productRows = props.products
           .map((product) => {
               // Determine the price based on discount
               const price = product.discount > 0 && product.apply_discount
                   ? product.discounted_price  // Use discounted price if discount is applied
                   : product.selling_price;    // Use selling price if no discount

               return `
           <tr>
             <td>${product.name}</td>
             <td style="text-align: center;">${product.quantity}</td>
             <td>
               ${product.discount > 0 && product.apply_discount
                       ? `<div style="font-weight: bold; font-size: 7px; background-color:black; color:white;text-align:center;">${product.discount}% off</div>`
                       : ""
                   }
               <div>${product.selling_price}</div>
             </td>
           </tr>
         `;
           })
           .join("");


       // Generate the receipt HTML



const receiptHTML = `
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Receipt</title>
        <style>
            @media print {
                body {
                    margin: 0;
                    padding: 0;
                    -webkit-print-color-adjust: exact;
                }
            }
            body {
                background-color: #ffffff;
                font-size: 12px;
                font-family: "Arial", sans-serif;
                margin: 0;
                padding: 10px;
                color: #000;
            }
            .header {
                text-align: center;
                margin-bottom: 16px;
            }
            .header h1 {
                font-size: 20px;
                font-weight: bold;
                margin: 0;
            }
            .header p {
                font-size: 10px;
                margin: 4px 0;
            }
            .section {
                margin-bottom: 16px;
                padding-top: 8px;
                border-top: 1px solid #000;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                font-size: 12px;
                margin-top: 8px;
            }
            .info-row p {
                margin: 0;
                font-weight: bold;
            }
            .info-row small {
                font-weight: normal;
            }
            table {
                width: 100%;
                font-size: 12px;
                border-collapse: collapse;
                margin-top: 8px;
            }
            table th,
            table td {
                padding: 6px 8px;
            }
            table th {
                text-align: left;
            }
            table td {
                text-align: right;
            }
            table td:first-child {
                text-align: left;
            }
            .totals {
                border-top: 1px solid #000;
                padding-top: 8px;
                font-size: 12px;
            }
            .totals div {
                display: flex;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            .totals div:nth-child(4) {
                font-size: 14px;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                font-size: 10px;
                margin-top: 16px;
            }
            .footer p {
                margin: 6px 0;
            }
            .footer .italic {
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="header">

 <!-- Header Section -->
<div style="border-bottom: 1px solid #000; padding-bottom: 10px; margin-bottom: 10px;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start;">

    <!-- Logo (Left) -->
    <div style="flex-shrink: 0;text-align: right;">
      <img src="/images/billlogo.png" alt="Company Logo"
           style="width: 60px; height: 60px; object-fit: contain;" />
    </div>

    <!-- Company Info (Right) -->
    <div style="text-align: right; flex-grow: 1; padding-left: 15px; color: #000;">

      <!-- Company Name -->
      ${companyInfo?.value?.name
        ? `<h1 style="margin: 0; font-size: 16px; font-weight: bold;">${companyInfo.value.name}</h1>`
        : ''
      }

      <!-- Address -->
      ${companyInfo?.value?.address
        ? `<p style="margin: 2px 0; font-size: 12px;">${companyInfo.value.address}</p>`
        : ''
      }

      <!-- Phones and Email -->
      ${(companyInfo?.value?.phone || companyInfo?.value?.phone2 || companyInfo?.value?.email)
        ? `<p style="margin: 2px 0; font-size: 12px;">
            ${companyInfo.value.phone || ''}
            ${companyInfo.value.phone2 ? ' | ' + companyInfo.value.phone2 : ''}
            ${companyInfo.value.email ? ' | ' + companyInfo.value.email : ''}
          </p>`
        : ''
      }

      <!-- Website -->
      ${companyInfo?.value?.website
        ? `<p style="margin: 2px 0; font-size: 12px;">${companyInfo.value.website}</p>`
        : ''
      }

    </div>
  </div>
</div>





            </div>




  <div class="info-row">
                  <div>
                      <p>Date:</p>
                      <small>${new Date().toLocaleDateString()} </small>
                  </div>
                  <div>
                      <p>Order No:</p>
                      <small>${props.orderid}</small>
                  </div>
              </div>
              <div class="info-row">
                  <div>
                      <p>Customer:</p>
                      <small>${props.customer.name}</small>
                  </div>
                  <div>
                      <p>Cashier:</p>
                      <small>${props.cashier.name}</small>
                  </div>
              </div>




           <div class="info-row">
  <p>Billing Type: <small>${props.isWholesale ? 'Wholesale' : 'Retail'}</small></p>
  <p>Billing Type: <small>${props.isWholesale ? 'Wholesale' : 'Retail'}</small></p>
  <p>Credit Bill: <small>${props.credit_bill ? 'Yes' : 'No'}</small></p>
</div>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="text-align: center;">Qty</th>
                        <th style="text-align: right;">Price</th>
                    </tr>
                </thead>
                <tbody>
                    ${productRows}
                </tbody>
            </table>
        </div>
        <div class="totals">
            <div>
                <span>Sub Total</span>
                <span>${(Number(props.subTotal) || 0).toFixed(2)} LKR</span>
            </div>
            <div>
                <span>Discount</span>
                <span>${(Number(props.totalDiscount) || 0).toFixed(2)} LKR</span>
            </div>
            <div>
                <span>Custom Discount</span>
                <span>
                    ${(Number(props.custom_discount) || 0).toFixed(2)} ${props.custom_discount_type === 'percent' ? '%' : props.custom_discount_type === 'fixed' ? 'LKR' : ''}
                </span>
            </div>
            <div>
                <span>Total</span>
                <span>${(Number(props.total) || 0).toFixed(2)} LKR</span>
            </div>
            <div>
                <span>Cash</span>
                <span>${(Number(props.cash) || 0).toFixed(2)} LKR</span>
            </div>
            <div style="font-weight: bold;">
                <span>Balance</span>
                <span>${(Number(props.balance) || 0).toFixed(2)} LKR</span>
            </div>



 <div style="font-weight: bold;">
                <span>Guide Name </span>
                <span>${props.guide_name || 'N/A'}</span>
            </div>



 <div style="font-weight: bold;">
                <span>Guide Commission </span>
                <span>${props.guide_comi || '0'}%</span>
            </div>

 <div style="font-weight: bold;">
                <span>Guide Cash </span>
                <span>${(Number(props.guide_cash) || 0).toFixed(2)} LKR</span>
            </div>

            <div style="font-weight: bold;">
                <span>Guide Status </span>
                <span>${getGuideStatus()}</span>
            </div>

  </div>

        <div class="footer">
            <p>THANK YOU COME AGAIN</p>
            <p class="italic">Let the quality define its own standards</p>
            <p style="font-weight: bold;">Powered by JAAN Network Ltd.</p>
            <p>${new Date().toLocaleTimeString()}</p>
        </div>
    </body>
</html>
`;









       // Open a new window
       const printWindow = window.open("", "_blank");
       if (!printWindow) {
           alert("Failed to open print window. Please check your browser settings.");
           return;
       }

       // Write the content to the new window
       printWindow.document.open();
       printWindow.document.write(receiptHTML);
       printWindow.document.close();

       // Wait for the content to load before triggering print
       printWindow.onload = () => {
           printWindow.focus();
           printWindow.print();
           printWindow.close();
       };
   };











</script>

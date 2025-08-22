<template>
  <TransitionRoot as="template" :show="open">
    <Dialog class="relative z-10" @close="$emit('update:open', false)">
      <!-- Modal Overlay -->
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div
          class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
        />
      </TransitionChild>

      <!-- Modal Content -->
      <div class="fixed inset-0 z-10 flex items-center justify-center">
        <TransitionChild
          as="template"
          enter="ease-out duration-300"
          enter-from="opacity-0 scale-95"
          enter-to="opacity-100 scale-100"
          leave="ease-in duration-200"
          leave-from="opacity-100 scale-100"
          leave-to="opacity-0 scale-95"
        >
          <DialogPanel
            class="bg-white text-black border-4 border-blue-600 rounded-[20px] shadow-xl w-5/6 lg:w-3/6 p-6"
          >
            <div
              class="flex flex-col items-start justify-start w-full h-full px-2 pt-4"
            >
              <div
                class="flex justify-center w-full h-full py-4 space-x-8 items-start-center"
              >
                <!-- Left Side: Image -->
                <div class="w-1/2">
                  <img
                    :src="
                      selectedProduct.image
                        ? `/${selectedProduct.image}`
                        : '/images/placeholder.jpg'
                    "
                    alt="Product Image"
                    class="object-cover h-full rounded-2xl"
                  />
                </div>

                <!-- Right Side: Text Content -->
                <div class="flex flex-col justify-between w-1/2 h-full">
                  <div class="flex items-center justify-between">
                    <!-- Product Name -->
                    <p class="text-3xl font-bold text-black w-full break-words">
                      {{ selectedProduct.name }}

                      <span
                        v-if="
                          selectedProduct.discount &&
                          selectedProduct.discount > 0
                        "
                        class="inline-block px-2 py-2 text-sm font-medium text-white bg-red-600 rounded"
                      >
                        {{ selectedProduct.discount }} % OFF
                      </span>
                    </p>

                    <!-- Discounted Price -->
                  </div>

                  <p
                    class="pb-6 mt-2 text-[#00000099] text-xl font-normal italic"
                  >
                    {{ selectedProduct.category?.name ?? "No Category" }}
                  </p>

                  <p class="pb-6 text-2xl font-bold text-black">
                    <span class="text-[#00000099] font-normal">Supplier : </span
                    >{{ selectedProduct.supplier?.name || "N/A" }}
                  </p>

                  <p class="pb-6 text-2xl font-bold text-black">
                    <span class="text-[#00000099] font-normal"
                      >Product Code :
                    </span>

                    {{ selectedProduct?.code ?? "N/A" }}
                  </p>
                  <p class="pb-6 text-2xl font-bold text-black">
                    <span class="text-[#00000099] font-normal"
                      >Batch No :
                    </span>

                    {{ selectedProduct?.batch_no ?? "N/A" }}
                  </p>


                  <div
                    class="flex items-center justify-between w-full text-2xl"
                  >
                    <div class="flex flex-col w-full">
                      <p
                        class="text-justify text-[#00000099] text-2xl flex items-center pb-6"
                      >
                        Color :

                        <span class="font-bold text-black">
                          {{ selectedProduct?.color?.name ?? "N/A" }}
                        </span>
                      </p>
                    </div>
                  </div>

                  <div
                    class="flex items-center justify-between w-full text-2xl"
                  >
                    <div class="flex flex-col w-full">
                      <p class="text-[#00000099] text-2xl pb-6">
                        Size :
                        <span
                          class="px-2 py-2 font-bold text-black border-2 border-gray-800 rounded-xl"
                        >
                          {{ selectedProduct?.size?.name ?? "N/A" }}
                        </span>
                      </p>
                    </div>
                  </div>

                  <div
                    class="flex items-center justify-between w-full pb-6 text-2xl"
                  >
                    <div class="flex flex-col w-full">
                      <p class="text-[#00000099]">Selling Price :</p>
                      <p class="font-bold text-black">
                        {{ selectedProduct?.selling_price ?? "N/A" }}
                        LKR
                      </p>
                    </div>
                    <div class="flex flex-col w-full">
                      <p class="text-[#00000099]">Cost Price :</p>
                      <p class="font-bold text-black">
                        {{ selectedProduct?.cost_price ?? "N/A" }}

                        LKR
                      </p>
                    </div>
                  </div>

                  <div
                    class="flex items-center justify-between w-full pb-6 text-2xl"
                  >
                    <div
                      class="flex flex-col w-full"
                      v-if="
                        selectedProduct.discount && selectedProduct.discount > 0
                      "
                    >
                      <p class="text-[#00000099]">Discount Price :</p>
                      <p class="font-bold text-black">
                        {{
                          selectedProduct.selling_price &&
                          selectedProduct.discount &&
                          selectedProduct.discount > 0
                            ? (
                                selectedProduct.selling_price -
                                (selectedProduct.selling_price *
                                  selectedProduct.discount) /
                                  100
                              ).toFixed(2)
                            : selectedProduct.selling_price
                        }}
                        LKR
                      </p>
                    </div>
                    <div class="flex flex-col w-full">
                      <p class="text-[#00000099]">Quantity :</p>
                      <p class="font-bold text-black">
                        {{ selectedProduct?.stock_quantity ?? "N/A" }}
                      </p>
                    </div>
                  </div>

                  <p class="pb-8 text-2xl font-bold text-black">
                    <span class="text-[#00000099] font-normal"
                      >Created On :
                    </span>
                    {{ formattedDate }}
                  </p>

                  <div class="mt-2">
              <!-- Inside Right Side under Print Barcode Button -->
<div class="mt-4 w-full">
  <label class="block mb-1 text-xl text-gray-700">Number of Barcodes:</label>
  <input
    type="number"
    v-model="barcodeCount"
    min="1"
    class="w-full px-4 py-2 text-xl border rounded focus:outline-none focus:ring focus:ring-blue-300"
  />
</div>

<button
  v-if="HasRole(['Admin', 'Manager'])"
  class="w-full px-4 py-3 mt-4 text-2xl font-semibold tracking-widest text-white bg-blue-600 rounded-xl hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2"
  @click="generateAndPrintBarcodes"
>
  Print Bar Codes
</button>
                  </div>
                </div>
              </div>

              <!-- Hidden container for printing -->




<!-- Print container for multiple barcodes -->
<div :class="{ hidden: !isVisible }" id="printContainer" class="print-container">
  <div class="print-wrapper">
    <div
      class="print-content"
      v-for="n in barcodeCount"
      :key="n"
    >
      <p class="product-code">{{ selectedProduct?.name || "N/A" }}</p>
      <svg :id="`barcodePrint${n}`"></svg>
      <div class="product-details">
        <p class="product-category">{{ selectedProduct?.code ?? "N/A" }}</p>
        <p class="product-price">{{ selectedProduct?.selling_price ?? "N/A" }} LKR</p>
      </div>
    </div>
  </div>
</div>








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
import { ref, watch, computed } from "vue";
import { useForm } from "@inertiajs/vue3";
import dayjs from "dayjs";
import { HasRole } from "@/Utils/Permissions";

const playClickSound = () => {
  const clickSound = new Audio("/sounds/click-sound.mp3");
  clickSound.play();
};

// Extend Day.js for ordinal formatting
import advancedFormat from "dayjs/plugin/advancedFormat";
dayjs.extend(advancedFormat);

const emit = defineEmits(["update:open"]);

// The `open` prop controls the visibility of the modal
const { selectedProduct } = defineProps({
  open: {
    type: Boolean,
    required: true,
  },
  categories: {
    type: Array,
    required: true,
  },
  colors: {
    type: Array,
    required: true,
  },
  sizes: {
    type: Array,
    required: true,
  },
  selectedProduct: {
    type: Object,
    default: null, // Ensure it defaults to null
  },
});

// Computed property to format the date
const formattedDate = computed(() =>
  selectedProduct && selectedProduct.created_at
    ? dayjs(selectedProduct.created_at).format("Do MMMM YYYY")
    : ""
);



const barcodeCount = ref(1);



 function generateAndPrintBarcodes() {
  const barcode = selectedProduct?.barcode
  const count = parseInt(barcodeCount.value)

  if (!barcode || barcode.trim() === '') {
    alert('Please enter a barcode value.')
    return
  }

  if (!count || count < 1) {
    alert('Please enter a valid number of barcodes.')
    return
  }

  const rows = []
  for (let i = 0; i < count; i += 2) {
    const first = i + 1
    const second = i + 2
    rows.push([first, second])
  }

  const htmlContent = `
    <html>
    <head>
     <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {    font-family: "Poppins", sans-serif; monospace; background: white; }

        .barcode-container {
          width: 75mm;
          margin: 0 auto;
          padding: 0;
          display: flex;
          flex-direction: column;
          gap: 0mm;
        }

        .barcode-row {
          display: flex;
          justify-content: space-between;
          gap: 5mm;
        }

        .barcode-label {

          width: 37.5mm;
          height: 25mm;
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
          padding: 0.2mm 0.5mm;
          text-align: center;
          background: white;
          box-sizing: border-box;
          overflow: hidden;
        }

        .product-name {
          font-size: 10px;
          font-weight: bold;
          line-height: 1;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
          width: 100%;
        }

      .barcode-svg {
  width: 100%;
  height: 12mm;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
    padding: 0 2mm;
}

.barcode-svg svg {
  width: 100%;
  height: 100%;
}


   .bottom-info {
    display: flex;
    justify-content: space-between;
    font-size: 9px;
    width: 100%;
    padding: 1mm 1mm;
    white-space: nowrap;
    font-weight: 600;
    font-feature-settings: "tnum", "zero"; /* Tabular numbers, normal zero */
    font-variant-numeric: tabular-nums; /* Ensures equal digit width */
  }


        .bottom-info .span1 {
          max-width: 30%;
          overflow: hidden;
          text-overflow: ellipsis;
        }
 .bottom-info .span2 {
          max-width: 68%;
          overflow: hidden;
          text-overflow: ellipsis;
        }


        @media print {
          @page {
            margin: 0;
            size: 75mm auto;
          }

          body {
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }

          .barcode-label {
            border: none;
            break-inside: avoid;
          }

          .barcode-row {
            break-inside: avoid;
          }
        }
      </style>
    </head>
    <body>
      <div class="barcode-container">
        ${rows
          .map(
            ([first, second]) => `
          <div class="barcode-row">
            ${[first, second]
              .map((i) =>
                i <= count
                  ? `
              <div class="barcode-label">
                <div class="product-name">${selectedProduct.name || 'N/A'}</div>
                <div class="barcode-svg"><svg id="barcode${i}"></svg></div>
                <div class="bottom-info">
                  <span class="span1">${selectedProduct.code || 'N/A'}</span>
                  <span  class="span2">${selectedProduct.selling_price ?? 'N/A'} LKR</span>
                </div>
              </div>
            `
                  : '<div class="barcode-label"></div>'
              )
              .join('')}
          </div>
        `
          )
          .join('')}
      </div>
    </body>
    </html>
  `

  const printWindow = window.open('', '_blank')
  printWindow.document.write(htmlContent)
  printWindow.document.close()

  printWindow.onload = () => {
    for (let i = 1; i <= count; i++) {
      const svg = printWindow.document.getElementById(`barcode${i}`)
      JsBarcode(svg, barcode, {
        format: 'CODE128',
        lineColor: '#000',
        width: 1.2,
        height: 35,
        displayValue: false,
        margin: 0,
      })
    }

    setTimeout(() => {
      printWindow.focus()
      printWindow.print()
      printWindow.close()
    }, 500)
  }
}


</script>

<style>
@media print {
  /* Label container */
  #printContainer {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    margin-top: 0;
  }

  /* Print content */
  .print-content {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100%;
    width: 100%;
    margin-top: 2mm;
  }

  /* Barcode centered and full width */
  #barcodePrint {
    width: 100%;
    margin-left: 12mm;
  }

  /* Product details */
  .product-details {
    display: flex;
    justify-content: space-around;
    align-items: center;
    width: 100%;
    font-size: 10px;
    font-weight: bold;
    margin-bottom: 5px;
    margin-left: 12mm;
  }

  .product-category,
  .product-price {
    color: #000;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  /* Product code */
  .product-code {
    color: #000;
    font-size: 10px;
    font-weight: bold;
    margin-top: 5px;
    margin-left: 10mm;
  }
}
</style>


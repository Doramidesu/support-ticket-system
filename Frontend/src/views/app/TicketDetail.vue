<script setup>
import { onMounted, onUnmounted, ref, nextTick } from "vue";
import { useRouter } from "vue-router";
import { useTicketStore } from "@/stores/ticket";
import { storeToRefs } from "pinia";
import { capitalize } from "lodash";
import feather from "feather-icons";
import { DateTime } from "luxon";
import { useRoute } from "vue-router";

const ticketStore = useTicketStore();
const { success, error, loading } = storeToRefs(ticketStore);
const { fetchTicket, createTicketReply } = ticketStore;

const route = useRoute();
const router = useRouter();
let pollingInterval = null;
const lastReplyRef = ref(null);

// TODO: Create refs for ticket and form
// Hint: You'll need ticket object and form with content field
const ticket = ref({});
const form = ref({
  status: "",
  content: "",
});

const fetchTicketDetail = async () => {
  const response = await fetchTicket(route.params.code);

  ticket.value = response;
  form.value.status = response.status;

  // ⬇️ tunggu DOM update, lalu scroll
  await nextTick();
  scrollToLastReply();
};

const handleSubmit = async () => {
  await createTicketReply(route.params.code, form.value);

  form.value.content = "";

  // 🛑 stop polling biar bersih
  if (pollingInterval) {
    clearInterval(pollingInterval);
    pollingInterval = null;
  }

  router.push({ name: "admin.ticket" });
};

onMounted(async () => {
  await fetchTicketDetail();
  feather.replace();

  // 🔄 realtime polling tiap 5 detik
  pollingInterval = setInterval(() => {
    fetchTicketDetail();
  }, 5000);
});

onUnmounted(() => {
  if (pollingInterval) {
    clearInterval(pollingInterval);
  }
});

const scrollToLastReply = () => {
  if (lastReplyRef.value) {
    lastReplyRef.value.scrollIntoView({
      behavior: "smooth",
      block: "start",
    });
  }
};
</script>

<template>
  <!-- Back Button -->
  <div class="mb-6">
    <RouterLink
      :to="{ name: 'app.dashboard' }"
      class="inline-flex items-center text-sm text-gray-600 hover:text-gray-800"
    >
      <i data-feather="arrow-left" class="w-4 h-4 mr-2"></i>
      Kembali ke Daftar Tiket
    </RouterLink>
  </div>

  <!-- Ticket Info -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-6">
      <div class="flex items-start justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-800">{{ ticket.title }}</h1>
          <div class="mt-2 flex items-center space-x-4">
            <span
              class="px-3 py-1 text-sm font-medium text-blue-700 bg-blue-100 rounded-full"
            >
              {{ capitalize(ticket.status) }}
            </span>
            <span
              class="px-3 py-1 text-sm font-medium text-red-700 bg-red-100 rounded-full"
            >
              {{ capitalize(ticket.priority) }}
            </span>
            <span class="text-sm text-gray-500">#{{ ticket.code }}</span>
            <span class="text-sm text-gray-500">
              Dibuat pada
              {{
                DateTime.fromISO(ticket.created_at).toFormat(
                  "dd MMMM yyyy, HH:mm"
                )
              }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Discussion Thread -->
  <div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <!-- Thread Header -->
    <div
      v-for="(reply, index) in ticket.ticket_replies"
      :key="reply.id"
      class="p-6 border-b border-gray-100"
      :ref="index === ticket.ticket_replies.length - 1 ? lastReplyRef : null"
    >
      <div class="flex items-start space-x-4">
        <img
          :src="`https://ui-avatars.com/api/?name=${reply.user.name}&background=0D8ABC&color=fff`"
          alt="User"
          class="w-10 h-10 rounded-full"
        />
        <div class="flex-1">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-sm font-medium text-gray-800">
                {{ reply.user.name }}
              </h4>
              <p class="text-xs text-gray-500">
                {{
                  DateTime.fromISO(reply.created_at).toFormat(
                    "dd MMMM yyyy, HH:mm"
                  )
                }}
              </p>
            </div>
          </div>
          <div class="mt-3 text-sm text-gray-800">
            <p>{{ reply.content }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Reply Form -->
    <div class="p-6 border-t border-gray-100">
      <h4 class="text-sm font-medium text-gray-800 mb-4">Tambah Balasan</h4>
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div class="group">
          <textarea
            v-model="form.content"
            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
            :class="{ 'border-red-500 ring-red-500': error?.content }"
            rows="4"
            placeholder="Tulis balasan Anda di sini..."
            minlength="10"
          ></textarea>
          <p class="mt-1 text-xs text-red-500" v-if="error?.content">
            {{ error?.content?.join(", ") }}
          </p>
        </div>
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4"></div>
          <button
            class="px-6 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700"
          >
            <i data-feather="send" class="w-4 h-4 inline-block mr-2"></i>
            <span v-if="!loading"> Kirim Balasan </span>
            <span v-else> Loading... </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

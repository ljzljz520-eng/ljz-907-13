<script setup>
import { ref, computed, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { X, Plus, MapPin, Calendar, Clock, User, Phone, Pencil, Trash2, History, Loader2, AlertCircle, ArrowLeft } from 'lucide-vue-next';
import axios from 'axios';
import { formatScreeningDate, formatScreeningTime, splitScreenings } from '../utils/screening';

const props = defineProps({
  isOpen: Boolean,
  movie: Object
});

const emit = defineEmits(['close', 'changed']);

const API = 'http://localhost:8000/api';

const view = ref('list'); // 'list' | 'form'
const loading = ref(false);
const saving = ref(false);
const error = ref(null);
const screenings = ref([]);
const showHistory = ref(false);
const editing = ref(null); // 正在编辑的排期，null 表示新增
const deletingId = ref(null);

const emptyForm = () => ({
  location: '',
  screening_date: '',
  start_time: '',
  contact_name: '',
  contact_phone: ''
});
const form = ref(emptyForm());

const groups = computed(() => splitScreenings(screenings.value));

const fetchScreenings = async () => {
  if (!props.movie) return;
  loading.value = true;
  error.value = null;
  try {
    const res = await axios.get(`${API}/screenings`, {
      params: { movie_id: props.movie.id, scope: 'all', per_page: 200 }
    });
    screenings.value = res.data.data;
  } catch (e) {
    error.value = '加载排期失败，请稍后重试';
  } finally {
    loading.value = false;
  }
};

watch(() => props.isOpen, (open) => {
  if (open) {
    view.value = 'list';
    editing.value = null;
    showHistory.value = false;
    fetchScreenings();
  }
});

const openCreate = () => {
  editing.value = null;
  form.value = emptyForm();
  error.value = null;
  view.value = 'form';
};

const openEdit = (s) => {
  editing.value = s;
  form.value = {
    location: s.location,
    screening_date: s.screening_date,
    start_time: formatScreeningTime(s.start_time),
    contact_name: s.contact_name,
    contact_phone: s.contact_phone || ''
  };
  error.value = null;
  view.value = 'form';
};

const save = async () => {
  saving.value = true;
  error.value = null;
  try {
    const payload = { ...form.value, movie_id: props.movie.id };
    if (editing.value) {
      await axios.put(`${API}/screenings/${editing.value.id}`, payload);
    } else {
      await axios.post(`${API}/screenings`, payload);
    }
    await fetchScreenings();
    emit('changed');
    view.value = 'list';
  } catch (e) {
    error.value = e.response?.data?.error || '保存失败，请检查填写内容';
  } finally {
    saving.value = false;
  }
};

const remove = async (s) => {
  deletingId.value = s.id;
  error.value = null;
  try {
    await axios.delete(`${API}/screenings/${s.id}`);
    await fetchScreenings();
    emit('changed');
  } catch (e) {
    error.value = '删除失败，请稍后重试';
  } finally {
    deletingId.value = null;
  }
};
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-[60]">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out" enter-from="opacity-0" enter-to="opacity-100"
        leave="duration-200 ease-in" leave-from="opacity-100" leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out" enter-from="opacity-0 scale-95" enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in" leave-from="opacity-100 scale-100" leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-lg transform overflow-hidden rounded-2xl bg-dark-800 p-6 shadow-xl transition-all border border-white/10">
              <div class="flex items-center justify-between mb-1">
                <DialogTitle as="h3" class="text-lg font-medium leading-6 text-white flex items-center gap-2">
                  <button v-if="view === 'form'" @click="view = 'list'" class="rounded-full p-1 text-gray-400 hover:bg-white/10 hover:text-white">
                    <ArrowLeft class="h-4 w-4" />
                  </button>
                  {{ view === 'list' ? '放映排期管理' : (editing ? '编辑排期' : '新增排期') }}
                </DialogTitle>
                <button @click="$emit('close')" class="text-gray-400 hover:text-white">
                  <X class="h-5 w-5" />
                </button>
              </div>
              <p class="mb-4 text-sm text-gray-500 truncate">《{{ movie?.translated_title || movie?.title }}》</p>

              <div v-if="error" class="mb-4 flex items-start gap-2 rounded bg-red-400/10 p-2 text-sm text-red-400">
                <AlertCircle class="h-4 w-4 mt-0.5 shrink-0" />
                <span>{{ error }}</span>
              </div>

              <!-- 列表视图 -->
              <div v-if="view === 'list'">
                <div class="mb-4 flex justify-end">
                  <button
                    @click="openCreate"
                    class="flex items-center gap-1.5 rounded-lg bg-purple-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-purple-500"
                  >
                    <Plus class="h-4 w-4" /> 新增排期
                  </button>
                </div>

                <div v-if="loading" class="flex justify-center py-10">
                  <Loader2 class="h-6 w-6 animate-spin text-purple-500" />
                </div>

                <template v-else>
                  <!-- 近期场次 -->
                  <div v-if="groups.upcoming.length" class="space-y-2">
                    <div
                      v-for="s in groups.upcoming" :key="s.id"
                      class="rounded-xl bg-white/5 p-3 ring-1 ring-white/10"
                    >
                      <div class="flex items-start justify-between gap-2">
                        <div class="space-y-1 text-sm">
                          <p class="flex items-center gap-2 text-emerald-300">
                            <Calendar class="h-3.5 w-3.5" />
                            {{ formatScreeningDate(s.screening_date) }}
                            <Clock class="h-3.5 w-3.5 ml-1" /> {{ formatScreeningTime(s.start_time) }}
                          </p>
                          <p class="flex items-center gap-2 text-gray-300">
                            <MapPin class="h-3.5 w-3.5 text-gray-500" /> {{ s.location }}
                          </p>
                          <p class="flex items-center gap-2 text-gray-400 text-xs">
                            <User class="h-3.5 w-3.5 text-gray-500" /> {{ s.contact_name }}
                            <span v-if="s.contact_phone" class="flex items-center gap-1">
                              <Phone class="h-3 w-3 text-gray-500" /> {{ s.contact_phone }}
                            </span>
                          </p>
                        </div>
                        <div class="flex shrink-0 gap-1">
                          <button @click="openEdit(s)" class="rounded-lg p-1.5 text-gray-400 hover:bg-white/10 hover:text-white" title="编辑">
                            <Pencil class="h-4 w-4" />
                          </button>
                          <button
                            @click="remove(s)" :disabled="deletingId === s.id"
                            class="rounded-lg p-1.5 text-gray-400 hover:bg-red-500/10 hover:text-red-400 disabled:opacity-50" title="删除"
                          >
                            <Loader2 v-if="deletingId === s.id" class="h-4 w-4 animate-spin" />
                            <Trash2 v-else class="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <p v-else class="rounded-xl bg-white/5 py-6 text-center text-sm text-gray-500">
                    暂无近期场次，点击右上角"新增排期"添加
                  </p>

                  <!-- 历史场次（过期自动归入） -->
                  <div v-if="groups.past.length" class="mt-4">
                    <button
                      @click="showHistory = !showHistory"
                      class="flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-300"
                    >
                      <History class="h-3.5 w-3.5" />
                      历史场次（{{ groups.past.length }}）
                      <span>{{ showHistory ? '收起' : '展开' }}</span>
                    </button>
                    <div v-if="showHistory" class="mt-2 space-y-2">
                      <div
                        v-for="s in groups.past" :key="s.id"
                        class="flex items-center justify-between gap-2 rounded-xl bg-white/[0.03] p-3 text-sm text-gray-500 ring-1 ring-white/5"
                      >
                        <div>
                          <p>{{ formatScreeningDate(s.screening_date) }} {{ formatScreeningTime(s.start_time) }} · {{ s.location }}</p>
                          <p class="text-xs text-gray-600">联系人：{{ s.contact_name }}<span v-if="s.contact_phone">（{{ s.contact_phone }}）</span></p>
                        </div>
                        <button
                          @click="remove(s)" :disabled="deletingId === s.id"
                          class="shrink-0 rounded-lg p-1.5 text-gray-600 hover:bg-red-500/10 hover:text-red-400 disabled:opacity-50" title="删除"
                        >
                          <Loader2 v-if="deletingId === s.id" class="h-4 w-4 animate-spin" />
                          <Trash2 v-else class="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  </div>
                </template>
              </div>

              <!-- 表单视图 -->
              <div v-else class="space-y-4">
                <div>
                  <label class="mb-1 block text-xs font-medium text-gray-400">放映地点 *</label>
                  <input
                    v-model="form.location" type="text" placeholder="如：社区文化中心报告厅"
                    class="w-full rounded-lg border-none bg-white/5 px-3 py-2 text-sm text-white placeholder-gray-600 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-purple-500"
                  />
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-gray-400">放映日期 *</label>
                    <input
                      v-model="form.screening_date" type="date"
                      class="w-full rounded-lg border-none bg-white/5 px-3 py-2 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-purple-500 [color-scheme:dark]"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-gray-400">场次时间 *</label>
                    <input
                      v-model="form.start_time" type="time"
                      class="w-full rounded-lg border-none bg-white/5 px-3 py-2 text-sm text-white ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-purple-500 [color-scheme:dark]"
                    />
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                  <div>
                    <label class="mb-1 block text-xs font-medium text-gray-400">联系人 *</label>
                    <input
                      v-model="form.contact_name" type="text" placeholder="姓名"
                      class="w-full rounded-lg border-none bg-white/5 px-3 py-2 text-sm text-white placeholder-gray-600 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                  <div>
                    <label class="mb-1 block text-xs font-medium text-gray-400">联系电话</label>
                    <input
                      v-model="form.contact_phone" type="text" placeholder="选填"
                      class="w-full rounded-lg border-none bg-white/5 px-3 py-2 text-sm text-white placeholder-gray-600 ring-1 ring-white/10 focus:outline-none focus:ring-2 focus:ring-purple-500"
                    />
                  </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                  <button @click="view = 'list'" class="px-4 py-2 text-sm font-medium text-gray-300 hover:text-white">取消</button>
                  <button
                    @click="save"
                    :disabled="saving || !form.location || !form.screening_date || !form.start_time || !form.contact_name"
                    class="flex items-center gap-2 rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <Loader2 v-if="saving" class="h-4 w-4 animate-spin" />
                    {{ saving ? '保存中...' : '保存' }}
                  </button>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

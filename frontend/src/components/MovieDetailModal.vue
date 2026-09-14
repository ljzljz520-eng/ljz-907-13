<script setup>
import { ref, computed, watch } from 'vue';
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue';
import { X, Star, Calendar, User, Tag, Globe, MessageSquare, Clock, Edit3, Award, Image as ImageIcon, ExternalLink, Ticket, MapPin, Phone, History, Trash2, Loader2, AlertTriangle, Settings } from 'lucide-vue-next';
import axios from 'axios';
import { formatScreeningDate, formatScreeningTime, splitScreenings } from '../utils/screening';

const props = defineProps({
  movie: Object,
  isOpen: Boolean
});

const emit = defineEmits(['close', 'manage-screenings', 'deleted']);

// 近期 / 历史场次分组（过期场次由后端标记，自动归入历史）
const screeningGroups = computed(() => splitScreenings(props.movie?.screenings));
const showHistory = ref(false);

watch(() => props.isOpen, (open) => {
  if (open) {
    showHistory.value = false;
    resetDeleteState();
  }
});

// ---- 删除影片 ----
const confirmingDelete = ref(false);
const deleting = ref(false);
const deleteError = ref(null);
// 删除被阻止（存在关联排期）时的提示
const blockedInfo = ref(null);

const resetDeleteState = () => {
  confirmingDelete.value = false;
  deleting.value = false;
  deleteError.value = null;
  blockedInfo.value = null;
};

const confirmDelete = async () => {
  if (!props.movie) return;
  deleting.value = true;
  deleteError.value = null;
  blockedInfo.value = null;
  try {
    await axios.delete(`http://localhost:8000/api/movies/${props.movie.id}`);
    emit('deleted', props.movie.id);
    resetDeleteState();
  } catch (e) {
    if (e.response?.status === 409) {
      // 存在关联排期：提示先处理排期
      blockedInfo.value = {
        message: e.response.data.error,
        count: e.response.data.screenings_count
      };
      confirmingDelete.value = false;
    } else {
      deleteError.value = e.response?.data?.error || '删除失败，请稍后重试';
    }
  } finally {
    deleting.value = false;
  }
};
</script>

<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/95 backdrop-blur-md" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 md:p-8">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-5xl transform overflow-hidden rounded-3xl bg-dark-800 shadow-2xl transition-all border border-white/5">
              
              <!-- Close Button -->
              <button 
                @click="$emit('close')" 
                class="absolute right-6 top-6 z-20 rounded-full bg-white/5 p-2 text-gray-400 hover:bg-white/10 hover:text-white transition"
              >
                <X class="h-6 w-6" />
              </button>

              <div class="relative flex flex-col lg:flex-row">
                
                <!-- Left Column: Poster -->
                <div class="w-full lg:w-[350px] shrink-0 p-6 md:p-10 lg:pr-0">
                  <div class="relative aspect-[2/3] w-full overflow-hidden rounded-2xl shadow-2xl ring-1 ring-white/10">
                    <img 
                      v-if="movie?.poster_url"
                      :src="movie.poster_url.includes('playwoool.com') ? `http://localhost:8000/api/proxy-image?url=${encodeURIComponent(movie.poster_url)}` : movie.poster_url" 
                      :alt="movie.title"
                      class="h-full w-full object-cover"
                      @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center bg-dark-700 text-gray-500" style="display: none;">
                      无海报
                    </div>
                  </div>
                </div>

                <!-- Right Column: Content -->
                <div class="flex flex-1 flex-col p-6 md:p-10">
                  
                  <DialogTitle as="h2" class="mb-2 text-3xl font-bold text-white md:text-4xl">
                    {{ movie?.translated_title || movie?.title }}
                  </DialogTitle>
                  <p class="mb-6 text-xl text-gray-400 font-medium">{{ movie?.title }} ({{ movie?.year }})</p>

                  <!-- Technical Info Grid -->
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 py-6 border-y border-white/5 mb-8 text-sm">
                    <div class="space-y-3">
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">译　　名</span>
                        <span class="text-gray-200">{{ movie?.translated_title || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">片　　名</span>
                        <span class="text-gray-200">{{ movie?.title || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">年　　代</span>
                        <span class="text-gray-200">{{ movie?.year || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">产　　地</span>
                        <span class="text-gray-200">{{ movie?.country || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">类　　别</span>
                        <span class="text-gray-200">{{ movie?.genre || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">语　　言</span>
                        <span class="text-gray-200">{{ movie?.language || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">上映日期</span>
                        <span class="text-gray-200">{{ movie?.release_date || '-' }}</span>
                      </div>
                    </div>
                    
                    <div class="space-y-3">
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">IMDb评分</span>
                        <div class="flex items-center gap-2">
                          <span class="text-yellow-500 font-bold">{{ movie?.imdb_rating || '-' }}</span>
                          <a v-if="movie?.imdb_link" :href="movie.imdb_link" target="_blank" class="text-blue-400 hover:text-blue-300">
                            <ExternalLink class="h-3 w-3" />
                          </a>
                        </div>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">豆瓣评分</span>
                        <div class="flex items-center gap-2">
                          <span class="text-green-500 font-bold">{{ movie?.rating || '-' }}</span>
                          <a v-if="movie?.douban_link" :href="movie.douban_link" target="_blank" class="text-blue-400 hover:text-blue-300">
                            <ExternalLink class="h-3 w-3" />
                          </a>
                        </div>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">片　　长</span>
                        <span class="text-gray-200">{{ movie?.runtime || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">导　　演</span>
                        <span class="text-gray-200">{{ movie?.director || '-' }}</span>
                      </div>
                      <div class="flex items-center gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">编　　剧</span>
                        <span class="text-gray-200">{{ movie?.writer || '-' }}</span>
                      </div>
                      <div class="flex items-start gap-3">
                        <span class="w-20 text-gray-500 font-bold shrink-0">主　　演</span>
                        <span class="text-gray-200 leading-relaxed">{{ movie?.actors || '-' }}</span>
                      </div>
                    </div>
                  </div>

                  <!-- Description -->
                  <div class="mb-10">
                    <div class="flex items-center gap-2 mb-4 text-white">
                      <div class="h-4 w-1 bg-purple-500 rounded-full"></div>
                      <h3 class="text-lg font-bold">简介</h3>
                    </div>
                    <p class="text-gray-400 leading-loose text-base whitespace-pre-wrap">
                      {{ movie?.description || '暂无相关简介资料。' }}
                    </p>
                  </div>

                  <!-- Awards -->
                  <div v-if="movie?.awards" class="mb-10">
                    <div class="flex items-center gap-2 mb-4 text-white">
                      <div class="h-4 w-1 bg-purple-500 rounded-full"></div>
                      <h3 class="text-lg font-bold">获奖情况</h3>
                    </div>
                    <p class="text-gray-400 leading-loose text-sm whitespace-pre-wrap italic">
                      {{ movie.awards }}
                    </p>
                  </div>

                  <!-- 公益放映排期 -->
                  <div class="mb-10">
                    <div class="flex items-center justify-between mb-4">
                      <div class="flex items-center gap-2 text-white">
                        <div class="h-4 w-1 bg-emerald-500 rounded-full"></div>
                        <h3 class="text-lg font-bold">公益放映</h3>
                      </div>
                      <button
                        @click="emit('manage-screenings', movie)"
                        class="flex items-center gap-1.5 rounded-lg bg-white/5 px-3 py-1.5 text-xs font-medium text-gray-300 ring-1 ring-white/10 transition hover:bg-white/10 hover:text-white"
                      >
                        <Settings class="h-3.5 w-3.5" /> 管理排期
                      </button>
                    </div>

                    <!-- 近期场次 -->
                    <div v-if="screeningGroups.upcoming.length" class="space-y-2">
                      <div
                        v-for="s in screeningGroups.upcoming" :key="s.id"
                        class="flex flex-wrap items-center gap-x-5 gap-y-1 rounded-xl bg-emerald-500/5 p-3 text-sm ring-1 ring-emerald-500/20"
                      >
                        <span class="flex items-center gap-1.5 font-medium text-emerald-300">
                          <Ticket class="h-4 w-4" />
                          {{ formatScreeningDate(s.screening_date) }} {{ formatScreeningTime(s.start_time) }}
                        </span>
                        <span class="flex items-center gap-1.5 text-gray-300">
                          <MapPin class="h-3.5 w-3.5 text-gray-500" /> {{ s.location }}
                        </span>
                        <span class="flex items-center gap-1.5 text-xs text-gray-400">
                          <User class="h-3.5 w-3.5 text-gray-500" /> {{ s.contact_name }}
                          <span v-if="s.contact_phone" class="flex items-center gap-1">
                            <Phone class="h-3 w-3 text-gray-500" /> {{ s.contact_phone }}
                          </span>
                        </span>
                      </div>
                    </div>
                    <p v-else class="rounded-xl bg-white/5 py-4 text-center text-sm text-gray-500">
                      近期暂无放映安排
                    </p>

                    <!-- 历史场次（过期自动归入） -->
                    <div v-if="screeningGroups.past.length" class="mt-3">
                      <button
                        @click="showHistory = !showHistory"
                        class="flex items-center gap-1.5 text-xs text-gray-500 transition hover:text-gray-300"
                      >
                        <History class="h-3.5 w-3.5" />
                        历史场次（{{ screeningGroups.past.length }}）
                        <span>{{ showHistory ? '收起' : '展开' }}</span>
                      </button>
                      <div v-if="showHistory" class="mt-2 space-y-1.5">
                        <p
                          v-for="s in screeningGroups.past" :key="s.id"
                          class="rounded-lg bg-white/[0.03] px-3 py-2 text-xs text-gray-500 ring-1 ring-white/5"
                        >
                          {{ formatScreeningDate(s.screening_date) }} {{ formatScreeningTime(s.start_time) }} · {{ s.location }} · 联系人 {{ s.contact_name }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <!-- 删除影片 -->
                  <div class="mt-auto border-t border-white/5 pt-6">
                    <!-- 存在关联排期时的阻止提示 -->
                    <div v-if="blockedInfo" class="mb-4 flex flex-wrap items-center gap-3 rounded-xl bg-amber-500/10 p-3 text-sm text-amber-300 ring-1 ring-amber-500/30">
                      <AlertTriangle class="h-4 w-4 shrink-0" />
                      <span class="flex-1">{{ blockedInfo.message }}</span>
                      <button
                        @click="emit('manage-screenings', movie)"
                        class="rounded-lg bg-amber-500/20 px-3 py-1.5 text-xs font-medium text-amber-200 transition hover:bg-amber-500/30"
                      >
                        去处理排期
                      </button>
                    </div>

                    <div v-if="deleteError" class="mb-4 rounded-xl bg-red-500/10 p-3 text-sm text-red-400 ring-1 ring-red-500/30">
                      {{ deleteError }}
                    </div>

                    <div v-if="!confirmingDelete" class="flex justify-end">
                      <button
                        @click="confirmingDelete = true"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-gray-500 transition hover:bg-red-500/10 hover:text-red-400"
                      >
                        <Trash2 class="h-3.5 w-3.5" /> 删除影片
                      </button>
                    </div>
                    <div v-else class="flex flex-wrap items-center justify-end gap-3">
                      <span class="text-sm text-gray-400">确认删除该影片？此操作不可恢复。</span>
                      <button
                        @click="confirmingDelete = false"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-gray-300 hover:text-white"
                      >
                        取消
                      </button>
                      <button
                        @click="confirmDelete" :disabled="deleting"
                        class="flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white transition hover:bg-red-500 disabled:opacity-50"
                      >
                        <Loader2 v-if="deleting" class="h-3.5 w-3.5 animate-spin" />
                        {{ deleting ? '删除中...' : '确认删除' }}
                      </button>
                    </div>
                  </div>

                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

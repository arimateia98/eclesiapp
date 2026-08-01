<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { localDateTimeToUtcIso } from '../services/dateTime'
import type {
  CreateEventInput,
  CreateEventTypeInput,
  CreateInternalMissionInput,
  CreateLocationInput,
  EventType,
  Location,
  MembershipRole,
  MinistryType,
  Mission,
  Organization,
  ScheduledEvent,
  ServiceFunction,
} from '../types/api'

const props = defineProps<{
  organization: Organization
  eventTypes: EventType[]
  locations: Location[]
  events: ScheduledEvent[]
  missions: Mission[]
  ministryTypes: MinistryType[]
  serviceFunctions: ServiceFunction[]
  selectedEventId: string | null
  loading: boolean
  loadingMissions: boolean
  busy: 'event-type' | 'location' | 'event' | 'mission' | null
  error: string | null
}>()

const emit = defineEmits<{
  refresh: []
  selectEvent: [eventId: string]
  createEventType: [input: CreateEventTypeInput]
  createLocation: [input: CreateLocationInput]
  createEvent: [input: CreateEventInput]
  createMission: [eventId: string, input: CreateInternalMissionInput]
}>()

const showCatalog = ref(false)
const showEventForm = ref(false)
const showMissionForm = ref(false)
const eventTypeName = ref('')
const locationForm = reactive<CreateLocationInput>({
  name: '',
  address_line: '',
  city: '',
  timezone: props.organization.timezone,
})
const eventForm = reactive({
  event_type_id: '',
  location_id: '',
  title: '',
  description: '',
  starts_at: '',
  ends_at: '',
})
const missionForm = reactive({
  ministry_type_id: '',
  title: '',
  description: '',
  slots: [{ service_function_id: '', quantity: 1, required: true }],
})

const catalogRoles: MembershipRole[] = ['owner', 'administrator']
const planningRoles: MembershipRole[] = ['owner', 'administrator', 'coordinator']
const canManageCatalog = computed(() =>
  props.organization.current_user_role !== null
    && catalogRoles.includes(props.organization.current_user_role),
)
const canPlan = computed(() =>
  props.organization.current_user_role !== null
    && planningRoles.includes(props.organization.current_user_role),
)
const selectedEvent = computed(() =>
  props.events.find((event) => event.id === props.selectedEventId) ?? null,
)
const availableFunctions = computed(() =>
  props.serviceFunctions.filter(
    (serviceFunction) => serviceFunction.ministry_type_id === missionForm.ministry_type_id,
  ),
)
const catalogReady = computed(() => props.eventTypes.length > 0)
const missionCatalogReady = computed(() =>
  props.ministryTypes.length > 0 && props.serviceFunctions.length > 0,
)

watch(
  () => missionForm.ministry_type_id,
  () => {
    missionForm.slots = [{ service_function_id: '', quantity: 1, required: true }]
  },
)

watch(
  () => props.busy,
  (busy, previousBusy) => {
    if (previousBusy === 'event-type' && busy === null && !props.error) {
      eventTypeName.value = ''
    }
    if (previousBusy === 'location' && busy === null && !props.error) {
      locationForm.name = ''
      locationForm.address_line = ''
      locationForm.city = ''
    }
    if (previousBusy === 'event' && busy === null && !props.error) {
      eventForm.title = ''
      eventForm.description = ''
      eventForm.starts_at = ''
      eventForm.ends_at = ''
      showEventForm.value = false
    }
    if (previousBusy === 'mission' && busy === null && !props.error) {
      missionForm.title = ''
      missionForm.description = ''
      missionForm.slots = [{ service_function_id: '', quantity: 1, required: true }]
      showMissionForm.value = false
    }
  },
)

function submitEventType(): void {
  emit('createEventType', { name: eventTypeName.value })
}

function submitLocation(): void {
  emit('createLocation', { ...locationForm })
}

function submitEvent(): void {
  emit('createEvent', {
    event_type_id: eventForm.event_type_id,
    location_id: eventForm.location_id || undefined,
    title: eventForm.title,
    description: eventForm.description || undefined,
    starts_at: localDateTimeToUtcIso(eventForm.starts_at, props.organization.timezone),
    ends_at: localDateTimeToUtcIso(eventForm.ends_at, props.organization.timezone),
  })
}

function submitMission(): void {
  if (!props.selectedEventId) {
    return
  }

  emit('createMission', props.selectedEventId, {
    ministry_type_id: missionForm.ministry_type_id,
    title: missionForm.title,
    description: missionForm.description || undefined,
    slots: missionForm.slots.map((slot) => ({ ...slot })),
  })
}

function addSlot(): void {
  missionForm.slots.push({ service_function_id: '', quantity: 1, required: true })
}

function removeSlot(index: number): void {
  if (missionForm.slots.length > 1) {
    missionForm.slots.splice(index, 1)
  }
}

function formatDate(value: string): string {
  return new Intl.DateTimeFormat('pt-BR', {
    dateStyle: 'medium',
    timeStyle: 'short',
    timeZone: props.organization.timezone,
  }).format(new Date(value))
}
</script>

<template>
  <section
    class="schedule-panel"
    aria-labelledby="schedule-title"
  >
    <div class="section-heading schedule-panel__heading">
      <div>
        <span class="section-kicker">Planejamento interno</span>
        <h2 id="schedule-title">
          Agenda e missões
        </h2>
        <p>Organize o evento e defina as funções necessárias antes de publicar a escala.</p>
      </div>
      <div class="schedule-panel__actions">
        <button
          class="text-button"
          type="button"
          :disabled="loading"
          @click="emit('refresh')"
        >
          {{ loading ? 'Atualizando…' : 'Atualizar' }}
        </button>
        <button
          v-if="canManageCatalog"
          class="secondary-button"
          type="button"
          data-test="toggle-scheduling-catalog"
          @click="showCatalog = !showCatalog"
        >
          {{ showCatalog ? 'Fechar catálogos' : 'Tipos e locais' }}
        </button>
        <button
          v-if="canPlan"
          class="primary-button primary-button--compact"
          type="button"
          data-test="toggle-event-form"
          :disabled="!catalogReady"
          @click="showEventForm = !showEventForm"
        >
          Novo evento
        </button>
      </div>
    </div>

    <div
      v-if="showCatalog && canManageCatalog"
      class="schedule-catalog"
      data-test="schedule-catalog"
    >
      <form
        class="compact-form"
        data-test="event-type-form"
        @submit.prevent="submitEventType"
      >
        <label class="field">
          <span>Tipo de evento</span>
          <input
            v-model.trim="eventTypeName"
            required
            minlength="2"
            placeholder="Ex.: Missa"
          >
        </label>
        <button
          class="secondary-button"
          type="submit"
          :disabled="busy !== null"
        >
          {{ busy === 'event-type' ? 'Adicionando…' : 'Adicionar tipo' }}
        </button>
      </form>

      <form
        class="compact-form schedule-location-form"
        data-test="location-form"
        @submit.prevent="submitLocation"
      >
        <label class="field">
          <span>Local</span>
          <input
            v-model.trim="locationForm.name"
            required
            minlength="2"
            placeholder="Ex.: Igreja Matriz"
          >
        </label>
        <label class="field">
          <span>Endereço <small>opcional</small></span>
          <input
            v-model.trim="locationForm.address_line"
            placeholder="Rua e número"
          >
        </label>
        <label class="field">
          <span>Cidade <small>opcional</small></span>
          <input
            v-model.trim="locationForm.city"
            placeholder="Fortaleza"
          >
        </label>
        <label class="field">
          <span>Timezone</span>
          <input
            v-model.trim="locationForm.timezone"
            required
          >
        </label>
        <button
          class="secondary-button"
          type="submit"
          :disabled="busy !== null"
        >
          {{ busy === 'location' ? 'Adicionando…' : 'Adicionar local' }}
        </button>
      </form>
    </div>

    <form
      v-if="showEventForm && canPlan"
      class="schedule-event-form"
      data-test="event-form"
      @submit.prevent="submitEvent"
    >
      <label class="field">
        <span>Tipo</span>
        <select
          v-model="eventForm.event_type_id"
          required
        >
          <option
            value=""
            disabled
          >Selecione</option>
          <option
            v-for="eventType in eventTypes"
            :key="eventType.id"
            :value="eventType.id"
          >
            {{ eventType.name }}
          </option>
        </select>
      </label>
      <label class="field">
        <span>Local <small>opcional</small></span>
        <select v-model="eventForm.location_id">
          <option value="">Sem local definido</option>
          <option
            v-for="location in locations"
            :key="location.id"
            :value="location.id"
          >
            {{ location.name }}
          </option>
        </select>
      </label>
      <label class="field field--wide">
        <span>Título</span>
        <input
          v-model.trim="eventForm.title"
          required
          minlength="2"
          placeholder="Ex.: Missa de Nossa Senhora"
        >
      </label>
      <label class="field">
        <span>Início</span>
        <input
          v-model="eventForm.starts_at"
          type="datetime-local"
          required
        >
      </label>
      <label class="field">
        <span>Término</span>
        <input
          v-model="eventForm.ends_at"
          type="datetime-local"
          required
        >
      </label>
      <label class="field field--wide">
        <span>Descrição <small>opcional</small></span>
        <textarea
          v-model.trim="eventForm.description"
          rows="3"
        />
      </label>
      <div class="schedule-form-actions field--wide">
        <button
          class="text-button"
          type="button"
          @click="showEventForm = false"
        >
          Cancelar
        </button>
        <button
          class="primary-button primary-button--compact"
          type="submit"
          :disabled="busy !== null"
        >
          {{ busy === 'event' ? 'Criando…' : 'Criar rascunho' }}
        </button>
      </div>
    </form>

    <div
      v-if="loading && events.length === 0"
      class="members-loading"
    >
      Carregando agenda…
    </div>
    <div
      v-else-if="events.length"
      class="schedule-layout"
    >
      <div
        class="event-list"
        data-test="event-list"
      >
        <button
          v-for="event in events"
          :key="event.id"
          class="event-card"
          :class="{ 'is-selected': event.id === selectedEventId }"
          type="button"
          :data-test="`select-event-${event.id}`"
          @click="emit('selectEvent', event.id)"
        >
          <span class="event-card__date">{{ formatDate(event.starts_at) }}</span>
          <strong>{{ event.title }}</strong>
          <span>{{ event.event_type.name }} · {{ event.location?.name || 'Local a definir' }}</span>
          <small>Rascunho privado</small>
        </button>
      </div>

      <aside class="mission-workspace">
        <template v-if="selectedEvent">
          <div class="mission-workspace__heading">
            <div>
              <span class="section-kicker">Missões do evento</span>
              <h3>{{ selectedEvent.title }}</h3>
            </div>
            <button
              v-if="canPlan"
              class="secondary-button"
              type="button"
              data-test="toggle-mission-form"
              :disabled="!missionCatalogReady"
              @click="showMissionForm = !showMissionForm"
            >
              Nova missão
            </button>
          </div>

          <form
            v-if="showMissionForm && canPlan"
            class="mission-form"
            data-test="mission-form"
            @submit.prevent="submitMission"
          >
            <label class="field">
              <span>Ministério</span>
              <select
                v-model="missionForm.ministry_type_id"
                required
              >
                <option
                  value=""
                  disabled
                >Selecione</option>
                <option
                  v-for="type in ministryTypes"
                  :key="type.id"
                  :value="type.id"
                >
                  {{ type.name }}
                </option>
              </select>
            </label>
            <label class="field">
              <span>Título</span>
              <input
                v-model.trim="missionForm.title"
                required
                minlength="2"
                placeholder="Ex.: Equipe de leitores"
              >
            </label>
            <label class="field field--wide">
              <span>Descrição <small>opcional</small></span>
              <textarea
                v-model.trim="missionForm.description"
                rows="2"
              />
            </label>

            <div class="mission-slots field--wide">
              <div
                v-for="(slot, index) in missionForm.slots"
                :key="index"
                class="mission-slot-row"
              >
                <label class="field">
                  <span>Função</span>
                  <select
                    v-model="slot.service_function_id"
                    required
                  >
                    <option
                      value=""
                      disabled
                    >Selecione</option>
                    <option
                      v-for="serviceFunction in availableFunctions"
                      :key="serviceFunction.id"
                      :value="serviceFunction.id"
                      :disabled="missionForm.slots.some((item, itemIndex) => itemIndex !== index && item.service_function_id === serviceFunction.id)"
                    >
                      {{ serviceFunction.name }}
                    </option>
                  </select>
                </label>
                <label class="field mission-slot-quantity">
                  <span>Quantidade</span>
                  <input
                    v-model.number="slot.quantity"
                    type="number"
                    min="1"
                    max="50"
                    required
                  >
                </label>
                <button
                  class="icon-button"
                  type="button"
                  aria-label="Remover vaga"
                  :disabled="missionForm.slots.length === 1"
                  @click="removeSlot(index)"
                >
                  ×
                </button>
              </div>
              <button
                class="text-button"
                type="button"
                :disabled="missionForm.slots.length >= 20"
                @click="addSlot"
              >
                + Adicionar outra função
              </button>
            </div>

            <div class="schedule-form-actions field--wide">
              <button
                class="text-button"
                type="button"
                @click="showMissionForm = false"
              >
                Cancelar
              </button>
              <button
                class="primary-button primary-button--compact"
                type="submit"
                :disabled="busy !== null"
              >
                {{ busy === 'mission' ? 'Criando…' : 'Criar missão' }}
              </button>
            </div>
          </form>

          <div
            v-if="loadingMissions"
            class="members-loading"
          >
            Carregando missões…
          </div>
          <div
            v-else-if="missions.length"
            class="mission-list"
            data-test="mission-list"
          >
            <article
              v-for="mission in missions"
              :key="mission.id"
              class="mission-card"
            >
              <div>
                <span>{{ mission.ministry_type.name }}</span>
                <strong>{{ mission.title }}</strong>
              </div>
              <ul>
                <li
                  v-for="slot in mission.slots"
                  :key="slot.id"
                >
                  {{ slot.quantity }}× {{ slot.service_function?.name }}
                </li>
              </ul>
            </article>
          </div>
          <div
            v-else
            class="catalog-empty"
          >
            <strong>Nenhuma missão neste evento</strong>
            <p>Defina as funções necessárias para preparar a escala.</p>
          </div>
        </template>
        <div
          v-else
          class="catalog-empty"
        >
          <strong>Selecione um evento</strong>
          <p>As missões e vagas aparecerão aqui.</p>
        </div>
      </aside>
    </div>
    <div
      v-else
      class="empty-state empty-state--compact"
    >
      <h3>Nenhum evento planejado</h3>
      <p v-if="catalogReady">
        Crie o primeiro rascunho para organizar as missões internas.
      </p>
      <p v-else>
        Cadastre primeiro um tipo de evento em “Tipos e locais”.
      </p>
    </div>
  </section>
</template>

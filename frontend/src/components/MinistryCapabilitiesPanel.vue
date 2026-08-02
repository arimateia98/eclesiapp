<script setup lang="ts">
import { computed, reactive, watch } from 'vue'
import type {
  CreateMinistryTypeInput,
  CreateServiceFunctionInput,
  MinistryType,
  Organization,
  OrganizationMembership,
  PersonFunction,
  ServiceFunction,
} from '../types/api'

const props = defineProps<{
  organization: Organization
  members: OrganizationMembership[]
  ministryTypes: MinistryType[]
  serviceFunctions: ServiceFunction[]
  personFunctions: PersonFunction[]
  selectedPersonId: string | null
  loading: boolean
  catalogBusy: 'type' | 'function' | null
  updatingFunctionId: string | null
  error: string | null
}>()

const emit = defineEmits<{
  refresh: []
  createMinistryType: [input: CreateMinistryTypeInput]
  createServiceFunction: [input: CreateServiceFunctionInput]
  selectPerson: [personId: string]
  toggleFunction: [serviceFunctionId: string, assigned: boolean]
}>()

const typeForm = reactive<CreateMinistryTypeInput>({ name: '', description: '' })
const functionForm = reactive<CreateServiceFunctionInput>({ ministry_type_id: '', name: '' })

const canManageCatalog = computed(() =>
  props.organization.current_user_role === 'owner'
  || props.organization.current_user_role === 'administrator',
)
const selectedMembership = computed(() =>
  props.members.find((membership) => membership.person.id === props.selectedPersonId) || null,
)
const assignedFunctionIds = computed(() =>
  new Set(props.personFunctions.map((assignment) => assignment.service_function_id)),
)

watch(
  () => props.ministryTypes,
  (ministryTypes) => {
    if (!ministryTypes.some((type) => type.id === functionForm.ministry_type_id)) {
      functionForm.ministry_type_id = ministryTypes[0]?.id || ''
    }
  },
  { immediate: true },
)

watch(
  () => props.catalogBusy,
  (busy, wasBusy) => {
    if (wasBusy === 'type' && busy === null && !props.error) {
      typeForm.name = ''
      typeForm.description = ''
    }

    if (wasBusy === 'function' && busy === null && !props.error) {
      functionForm.name = ''
    }
  },
)

function functionsFor(ministryTypeId: string): ServiceFunction[] {
  return props.serviceFunctions.filter((item) => item.ministry_type_id === ministryTypeId)
}

function handlePersonChange(event: unknown): void {
  const target = (event as { target?: { value?: unknown } }).target
  const personId = typeof target?.value === 'string' ? target.value : ''

  if (personId) {
    emit('selectPerson', personId)
  }
}
</script>

<template>
  <section
    class="capabilities-panel"
    aria-labelledby="capabilities-title"
  >
    <div class="section-heading capabilities-panel__heading">
      <div>
        <span class="section-kicker">Ministérios e talentos</span>
        <h2 id="capabilities-title">
          Funções de serviço
        </h2>
      </div>
      <button
        class="text-button"
        type="button"
        :disabled="loading"
        @click="emit('refresh')"
      >
        {{ loading ? 'Atualizando…' : 'Atualizar catálogo' }}
      </button>
    </div>

    <div class="capabilities-grid">
      <div class="catalog-column">
        <div
          v-if="canManageCatalog"
          class="catalog-forms"
        >
          <form
            class="compact-form"
            data-test="ministry-type-form"
            @submit.prevent="emit('createMinistryType', { ...typeForm })"
          >
            <label class="field">
              <span>Novo tipo de ministério</span>
              <input
                v-model.trim="typeForm.name"
                required
                placeholder="Ex.: Liturgia"
              >
            </label>
            <button
              class="secondary-button"
              type="submit"
              :disabled="catalogBusy !== null"
            >
              {{ catalogBusy === 'type' ? 'Criando…' : 'Adicionar tipo' }}
            </button>
          </form>

          <form
            class="compact-form"
            data-test="service-function-form"
            @submit.prevent="emit('createServiceFunction', { ...functionForm })"
          >
            <label class="field">
              <span>Nova função</span>
              <input
                v-model.trim="functionForm.name"
                required
                placeholder="Ex.: Leitor"
              >
            </label>
            <label class="field">
              <span>Ministério</span>
              <select
                v-model="functionForm.ministry_type_id"
                required
              >
                <option
                  disabled
                  value=""
                >Escolha um tipo</option>
                <option
                  v-for="ministryType in ministryTypes"
                  :key="ministryType.id"
                  :value="ministryType.id"
                >
                  {{ ministryType.name }}
                </option>
              </select>
            </label>
            <button
              class="secondary-button"
              type="submit"
              :disabled="catalogBusy !== null || ministryTypes.length === 0"
            >
              {{ catalogBusy === 'function' ? 'Criando…' : 'Adicionar função' }}
            </button>
          </form>
        </div>

        <div
          v-if="ministryTypes.length"
          class="ministry-list"
          data-test="ministry-catalog"
        >
          <article
            v-for="ministryType in ministryTypes"
            :key="ministryType.id"
            class="ministry-card"
          >
            <div>
              <strong>{{ ministryType.name }}</strong>
              <span>{{ functionsFor(ministryType.id).length }} funções</span>
            </div>
            <div class="function-pills">
              <span
                v-for="serviceFunction in functionsFor(ministryType.id)"
                :key="serviceFunction.id"
              >
                {{ serviceFunction.name }}
              </span>
              <small v-if="functionsFor(ministryType.id).length === 0">Nenhuma função cadastrada</small>
            </div>
          </article>
        </div>
        <div
          v-else
          class="catalog-empty"
        >
          <strong>O catálogo começa pelos ministérios.</strong>
          <p>Crie “Liturgia”, “Música” ou outro tipo usado por esta organização.</p>
        </div>
      </div>

      <aside class="person-capabilities">
        <span class="section-kicker">Competências pessoais</span>
        <h3>Atribua quem pode servir</h3>
        <label class="field">
          <span>Pessoa</span>
          <select
            :value="selectedPersonId || ''"
            data-test="capability-person"
            @change="handlePersonChange"
          >
            <option
              disabled
              value=""
            >Escolha uma pessoa</option>
            <option
              v-for="membership in members"
              :key="membership.person.id"
              :value="membership.person.id"
            >
              {{ membership.person.preferred_name || membership.person.full_name }}
            </option>
          </select>
        </label>

        <div
          v-if="selectedMembership && serviceFunctions.length"
          class="capability-list"
          data-test="capability-list"
        >
          <p>
            Funções de <strong>{{ selectedMembership.person.preferred_name || selectedMembership.person.full_name }}</strong>
          </p>
          <button
            v-for="serviceFunction in serviceFunctions"
            :key="serviceFunction.id"
            class="capability-toggle"
            :class="{ 'is-assigned': assignedFunctionIds.has(serviceFunction.id) }"
            type="button"
            :disabled="loading || updatingFunctionId !== null"
            :data-test="`toggle-function-${serviceFunction.id}`"
            @click="emit('toggleFunction', serviceFunction.id, assignedFunctionIds.has(serviceFunction.id))"
          >
            <span
              class="capability-toggle__check"
              aria-hidden="true"
            >
              {{ assignedFunctionIds.has(serviceFunction.id) ? '✓' : '+' }}
            </span>
            <span>
              <strong>{{ serviceFunction.name }}</strong>
              <small>{{ serviceFunction.ministry_type.name }}</small>
            </span>
          </button>
        </div>
        <p
          v-else-if="selectedMembership"
          class="capability-hint"
        >
          Cadastre ao menos uma função no catálogo para atribuir competências.
        </p>
        <p
          v-else
          class="capability-hint"
        >
          Selecione uma pessoa para consultar e ajustar as funções em que ela pode servir.
        </p>
      </aside>
    </div>
  </section>
</template>

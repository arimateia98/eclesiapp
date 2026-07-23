<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import MinistryCapabilitiesPanel from './MinistryCapabilitiesPanel.vue'
import type {
  AddOrganizationMemberInput,
  AuthSession,
  CreateMinistryTypeInput,
  CreateServiceFunctionInput,
  MembershipRole,
  MinistryType,
  Organization,
  OrganizationMembership,
  PersonFunction,
  ServiceFunction,
} from '../types/api'

const props = defineProps<{
  session: AuthSession
  organization: Organization
  members: OrganizationMembership[]
  ministryTypes: MinistryType[]
  serviceFunctions: ServiceFunction[]
  personFunctions: PersonFunction[]
  selectedPersonId: string | null
  loading: boolean
  loadingCatalog: boolean
  catalogBusy: 'type' | 'function' | null
  updatingFunctionId: string | null
  adding: boolean
  invitingPersonId: string | null
  error: string | null
  notice: string | null
}>()

const emit = defineEmits<{
  back: []
  logout: []
  refresh: []
  addMember: [input: AddOrganizationMemberInput]
  invite: [personId: string]
  refreshCatalog: []
  createMinistryType: [input: CreateMinistryTypeInput]
  createServiceFunction: [input: CreateServiceFunctionInput]
  selectPerson: [personId: string]
  toggleFunction: [serviceFunctionId: string, assigned: boolean]
}>()

const showMemberForm = ref(false)
const form = reactive<AddOrganizationMemberInput>({
  full_name: '',
  preferred_name: '',
  email: '',
  phone: '',
  role: 'member',
})

const roleLabels: Record<MembershipRole, string> = {
  owner: 'Proprietário',
  administrator: 'Administrador',
  coordinator: 'Coordenador',
  member: 'Membro',
  guest: 'Convidado',
}

const peopleWithoutAccount = computed(() =>
  props.members.filter((membership) => !membership.person.has_user).length,
)

watch(
  () => props.adding,
  (adding, wasAdding) => {
    if (wasAdding && !adding && !props.error) {
      form.full_name = ''
      form.preferred_name = ''
      form.email = ''
      form.phone = ''
      form.role = 'member'
      showMemberForm.value = false
    }
  },
)

function initials(name: string): string {
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0])
    .join('')
    .toUpperCase()
}

function submitMember(): void {
  emit('addMember', { ...form })
}
</script>

<template>
  <div class="dashboard-shell">
    <header class="topbar">
      <a
        class="brand"
        href="#"
        aria-label="Eclesiapp — início"
        @click.prevent="emit('back')"
      >
        <span
          class="brand__mark"
          aria-hidden="true"
        >E</span>
        <span>Eclesiapp</span>
      </a>
      <div class="topbar__actions">
        <span class="workspace-user">{{ session.user.name }}</span>
        <button
          class="text-button"
          type="button"
          @click="emit('logout')"
        >
          Sair
        </button>
      </div>
    </header>

    <main class="dashboard workspace">
      <button
        class="back-button"
        type="button"
        data-test="workspace-back"
        @click="emit('back')"
      >
        ← Todas as organizações
      </button>

      <section class="workspace-hero">
        <div class="workspace-hero__identity">
          <span class="organization-avatar organization-avatar--large">
            {{ initials(organization.name) }}
          </span>
          <div>
            <span class="eyebrow">Gestão de pessoas</span>
            <h1>{{ organization.name }}</h1>
            <p>{{ members.length }} pessoas · {{ peopleWithoutAccount }} ainda sem acesso</p>
          </div>
        </div>
        <button
          class="primary-button primary-button--compact"
          type="button"
          data-test="open-add-member"
          @click="showMemberForm = !showMemberForm"
        >
          <span aria-hidden="true">＋</span>
          Cadastrar pessoa
        </button>
      </section>

      <p
        v-if="notice"
        class="inline-notice"
        data-test="workspace-notice"
        role="status"
      >
        {{ notice }}
      </p>
      <p
        v-if="error"
        class="form-error form-error--wide"
        data-test="workspace-error"
        role="alert"
      >
        {{ error }}
      </p>

      <MinistryCapabilitiesPanel
        :organization="organization"
        :members="members"
        :ministry-types="ministryTypes"
        :service-functions="serviceFunctions"
        :person-functions="personFunctions"
        :selected-person-id="selectedPersonId"
        :loading="loadingCatalog"
        :catalog-busy="catalogBusy"
        :updating-function-id="updatingFunctionId"
        :error="error"
        @refresh="emit('refreshCatalog')"
        @create-ministry-type="emit('createMinistryType', $event)"
        @create-service-function="emit('createServiceFunction', $event)"
        @select-person="emit('selectPerson', $event)"
        @toggle-function="(serviceFunctionId, assigned) => emit('toggleFunction', serviceFunctionId, assigned)"
      />

      <section
        v-if="showMemberForm"
        class="create-panel"
        aria-labelledby="member-form-title"
      >
        <div class="create-panel__heading">
          <div>
            <span class="section-kicker">Pessoa sem conta</span>
            <h2 id="member-form-title">
              Cadastre quem participa
            </h2>
          </div>
          <button
            class="icon-button"
            type="button"
            aria-label="Fechar"
            @click="showMemberForm = false"
          >
            ×
          </button>
        </div>

        <form
          class="organization-form"
          data-test="member-form"
          @submit.prevent="submitMember"
        >
          <label class="field">
            <span>Nome completo</span>
            <input
              v-model.trim="form.full_name"
              required
              placeholder="Ex.: José da Silva"
            >
          </label>
          <label class="field">
            <span>Nome preferido <small>opcional</small></span>
            <input
              v-model.trim="form.preferred_name"
              placeholder="Ex.: Zé"
            >
          </label>
          <label class="field">
            <span>E-mail <small>necessário para convite</small></span>
            <input
              v-model.trim="form.email"
              type="email"
              placeholder="pessoa@exemplo.com"
            >
          </label>
          <label class="field">
            <span>Telefone <small>opcional</small></span>
            <input
              v-model.trim="form.phone"
              type="tel"
              placeholder="(85) 99999-9999"
            >
          </label>
          <label class="field">
            <span>Papel inicial</span>
            <select v-model="form.role">
              <option value="member">
                Membro
              </option>
              <option value="guest">
                Convidado
              </option>
              <option value="coordinator">
                Coordenador
              </option>
              <option value="administrator">
                Administrador
              </option>
            </select>
          </label>
          <div class="organization-form__actions">
            <button
              class="secondary-button"
              type="button"
              @click="showMemberForm = false"
            >
              Cancelar
            </button>
            <button
              class="primary-button primary-button--compact"
              type="submit"
              :disabled="adding"
            >
              {{ adding ? 'Cadastrando…' : 'Cadastrar pessoa' }}
            </button>
          </div>
        </form>
      </section>

      <section class="members-panel">
        <div class="section-heading members-panel__heading">
          <div>
            <span class="section-kicker">Equipe</span>
            <h2>Pessoas cadastradas</h2>
          </div>
          <button
            class="text-button"
            type="button"
            :disabled="loading"
            @click="emit('refresh')"
          >
            {{ loading ? 'Atualizando…' : 'Atualizar' }}
          </button>
        </div>

        <div
          v-if="loading && members.length === 0"
          class="members-loading"
        >
          Carregando pessoas…
        </div>
        <div
          v-else-if="members.length"
          class="member-list"
          data-test="member-list"
        >
          <article
            v-for="membership in members"
            :key="membership.id"
            class="member-row"
          >
            <span class="member-row__avatar">{{ initials(membership.person.full_name) }}</span>
            <div class="member-row__identity">
              <strong>{{ membership.person.preferred_name || membership.person.full_name }}</strong>
              <span>{{ membership.person.email || 'E-mail não informado' }}</span>
            </div>
            <span class="role-pill">{{ roleLabels[membership.role] }}</span>
            <span
              class="account-state"
              :class="{ 'has-account': membership.person.has_user }"
            >
              {{ membership.person.has_user ? 'Acesso ativo' : 'Sem acesso' }}
            </span>
            <button
              v-if="!membership.person.has_user"
              class="secondary-button invite-button"
              type="button"
              :disabled="!membership.person.email || invitingPersonId === membership.person.id"
              :data-test="`invite-${membership.person.id}`"
              @click="emit('invite', membership.person.id)"
            >
              {{ invitingPersonId === membership.person.id ? 'Enviando…' : 'Enviar convite' }}
            </button>
            <span
              v-else
              class="member-row__done"
              aria-label="Conta vinculada"
            >✓</span>
          </article>
        </div>
        <div
          v-else
          class="empty-state empty-state--compact"
        >
          <h3>Nenhuma pessoa cadastrada</h3>
          <p>Cadastre a primeira pessoa para começar a formar a equipe.</p>
        </div>
      </section>
    </main>
  </div>
</template>

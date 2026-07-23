<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import type {
  AuthSession,
  CreateOrganizationInput,
  Organization,
  OrganizationType,
  OrganizationVisibility,
} from '../types/api'

const props = defineProps<{
  session: AuthSession
  organizations: Organization[]
  loading: boolean
  creating: boolean
  error: string | null
  notice: string | null
  apiOnline: boolean
}>()

const emit = defineEmits<{
  logout: []
  refresh: []
  create: [input: CreateOrganizationInput]
  select: [organization: Organization]
}>()

const showCreateForm = ref(false)
const slugEdited = ref(false)
const form = reactive<{
  name: string
  slug: string
  type: OrganizationType
  visibility: OrganizationVisibility
  timezone: string
}>({
  name: '',
  slug: '',
  type: 'community',
  visibility: 'private',
  timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC',
})

const firstName = computed(() => props.session.user.name.trim().split(/\s+/)[0] || 'Olá')
const activeOrganizations = computed(() =>
  props.organizations.filter((organization) => organization.status === 'active'),
)

const typeLabels: Record<OrganizationType, string> = {
  diocese: 'Diocese',
  parish: 'Paróquia',
  community: 'Comunidade',
  chapel: 'Capela',
  ministry: 'Ministério',
  movement: 'Movimento',
  group: 'Grupo pastoral',
}

const visibilityLabels: Record<OrganizationVisibility, string> = {
  public: 'Pública',
  private: 'Privada',
  unlisted: 'Não listada',
}

watch(
  () => form.name,
  (name) => {
    if (!slugEdited.value) {
      form.slug = slugify(name)
    }
  },
)

watch(
  () => props.creating,
  (creating, wasCreating) => {
    if (wasCreating && !creating && !props.error) {
      resetForm()
      showCreateForm.value = false
    }
  },
)

function slugify(value: string): string {
  return value
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-|-$/g, '')
}

function resetForm(): void {
  form.name = ''
  form.slug = ''
  form.type = 'community'
  form.visibility = 'private'
  slugEdited.value = false
}

function submitOrganization(): void {
  emit('create', { ...form })
}

function initials(name: string): string {
  return name
    .split(/\s+/)
    .slice(0, 2)
    .map((word) => word[0])
    .join('')
    .toUpperCase()
}
</script>

<template>
  <div class="dashboard-shell">
    <header class="topbar">
      <a
        class="brand"
        href="#"
        aria-label="Eclesiapp — início"
      >
        <span
          class="brand__mark"
          aria-hidden="true"
        >E</span>
        <span>Eclesiapp</span>
      </a>

      <div class="topbar__actions">
        <span
          class="api-indicator"
          :class="{ 'is-online': apiOnline }"
        >
          <span aria-hidden="true" />
          {{ apiOnline ? 'Sistema online' : 'API indisponível' }}
        </span>
        <div class="user-chip">
          <span class="user-chip__avatar">{{ initials(session.user.name) }}</span>
          <span class="user-chip__copy">
            <strong>{{ session.user.name }}</strong>
            <small>{{ session.user.email }}</small>
          </span>
        </div>
        <button
          class="text-button"
          type="button"
          data-test="logout"
          @click="emit('logout')"
        >
          Sair
        </button>
      </div>
    </header>

    <main class="dashboard">
      <section class="dashboard-hero">
        <div>
          <span class="eyebrow">Visão geral</span>
          <h1>Olá, {{ firstName }}.</h1>
          <p>Escolha uma organização para continuar ou prepare um novo espaço pastoral.</p>
        </div>
        <button
          class="primary-button primary-button--compact"
          type="button"
          data-test="open-create-organization"
          @click="showCreateForm = !showCreateForm"
        >
          <span aria-hidden="true">＋</span>
          Nova organização
        </button>
      </section>

      <p
        v-if="notice"
        class="inline-notice"
        data-test="dashboard-notice"
        role="status"
      >
        {{ notice }}
      </p>
      <p
        v-if="error"
        class="form-error form-error--wide"
        data-test="dashboard-error"
        role="alert"
      >
        {{ error }}
      </p>

      <section
        v-if="showCreateForm"
        class="create-panel"
        aria-labelledby="create-title"
      >
        <div class="create-panel__heading">
          <div>
            <span class="section-kicker">Novo espaço</span>
            <h2 id="create-title">
              Cadastre uma organização
            </h2>
          </div>
          <button
            class="icon-button"
            type="button"
            aria-label="Fechar"
            @click="showCreateForm = false"
          >
            ×
          </button>
        </div>

        <form
          class="organization-form"
          @submit.prevent="submitOrganization"
        >
          <label class="field field--wide">
            <span>Nome da organização</span>
            <input
              v-model.trim="form.name"
              required
              placeholder="Ex.: Comunidade São José"
            >
          </label>

          <label class="field">
            <span>Tipo</span>
            <select v-model="form.type">
              <option
                v-for="(label, value) in typeLabels"
                :key="value"
                :value="value"
              >
                {{ label }}
              </option>
            </select>
          </label>

          <label class="field">
            <span>Visibilidade</span>
            <select v-model="form.visibility">
              <option value="private">Privada</option>
              <option value="public">Pública</option>
              <option value="unlisted">Não listada</option>
            </select>
          </label>

          <label class="field field--wide">
            <span>Endereço curto</span>
            <div class="slug-input">
              <span>eclesiapp/</span>
              <input
                v-model.trim="form.slug"
                required
                pattern="[a-z0-9-]+"
                placeholder="comunidade-sao-jose"
                @input="slugEdited = true"
              >
            </div>
          </label>

          <div class="organization-form__actions field--wide">
            <button
              class="secondary-button"
              type="button"
              @click="showCreateForm = false"
            >
              Cancelar
            </button>
            <button
              class="primary-button primary-button--compact"
              type="submit"
              :disabled="creating"
            >
              {{ creating ? 'Criando…' : 'Criar organização' }}
            </button>
          </div>
        </form>
      </section>

      <section
        class="organizations-section"
        aria-labelledby="organizations-title"
      >
        <div class="section-heading">
          <div>
            <span class="section-kicker">Seus espaços</span>
            <h2 id="organizations-title">
              Organizações
            </h2>
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
          v-if="loading && organizations.length === 0"
          class="loading-grid"
          aria-label="Carregando"
        >
          <span
            v-for="item in 3"
            :key="item"
          />
        </div>

        <div
          v-else-if="activeOrganizations.length"
          class="organization-grid"
          data-test="organization-list"
        >
          <article
            v-for="organization in activeOrganizations"
            :key="organization.id"
            class="organization-card"
          >
            <div class="organization-card__top">
              <span class="organization-avatar">{{ initials(organization.name) }}</span>
              <span class="visibility-pill">{{ visibilityLabels[organization.visibility] }}</span>
            </div>
            <div>
              <span class="organization-type">{{ typeLabels[organization.type] }}</span>
              <h3>{{ organization.name }}</h3>
              <p>{{ organization.timezone }}</p>
            </div>
            <button
              class="organization-card__link"
              type="button"
              :data-test="`open-${organization.id}`"
              @click="emit('select', organization)"
            >
              Abrir organização <span aria-hidden="true">→</span>
            </button>
          </article>
        </div>

        <div
          v-else
          class="empty-state"
          data-test="organizations-empty"
        >
          <span
            class="empty-state__mark"
            aria-hidden="true"
          >✦</span>
          <h3>Sua primeira organização começa aqui</h3>
          <p>Cadastre uma comunidade, paróquia, ministério ou grupo para reunir as pessoas certas.</p>
          <button
            class="secondary-button"
            type="button"
            @click="showCreateForm = true"
          >
            Cadastrar organização
          </button>
        </div>
      </section>
    </main>
  </div>
</template>

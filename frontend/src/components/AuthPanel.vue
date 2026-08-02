<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { ApiError, login, registerAccount } from '../services/api'
import type { AuthSession } from '../types/api'

const emit = defineEmits<{
  authenticated: [session: AuthSession]
}>()

type AuthMode = 'login' | 'register'

const mode = ref<AuthMode>('register')
const busy = ref(false)
const errorMessage = ref<string | null>(null)
const fields = reactive({
  name: '',
  fullName: '',
  preferredName: '',
  email: '',
  password: '',
  passwordConfirmation: '',
})

const isRegister = computed(() => mode.value === 'register')

function changeMode(nextMode: AuthMode): void {
  mode.value = nextMode
  errorMessage.value = null
}

function readableError(error: unknown): string {
  if (!(error instanceof ApiError)) {
    return 'Não foi possível conectar ao Eclesiapp. Tente novamente.'
  }

  const firstValidationMessage = Object.values(error.validationErrors).flat()[0]

  return firstValidationMessage || error.message
}

async function submit(): Promise<void> {
  busy.value = true
  errorMessage.value = null

  try {
    const session = isRegister.value
      ? await registerAccount({
          name: fields.name,
          full_name: fields.fullName,
          preferred_name: fields.preferredName || undefined,
          email: fields.email,
          password: fields.password,
          password_confirmation: fields.passwordConfirmation,
          device_name: 'painel-web',
        })
      : await login({
          email: fields.email,
          password: fields.password,
          device_name: 'painel-web',
        })

    emit('authenticated', session)
  } catch (error) {
    errorMessage.value = readableError(error)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <section
    class="auth-card"
    aria-labelledby="auth-title"
  >
    <div class="auth-card__intro">
      <span class="eyebrow">Sua comunidade, bem cuidada</span>
      <h1 id="auth-title">
        Organize quem serve. Cuide de quem participa.
      </h1>
      <p>
        Um espaço simples para reunir pessoas, ministérios e comunidades — com clareza desde o
        primeiro convite.
      </p>

      <div
        class="trust-list"
        aria-label="Recursos disponíveis"
      >
        <span><strong>01</strong> Pessoas e contas separadas</span>
        <span><strong>02</strong> Acesso por organização</span>
        <span><strong>03</strong> Histórico das ações importantes</span>
      </div>
    </div>

    <div class="auth-form-wrap">
      <div
        class="auth-tabs"
        role="tablist"
        aria-label="Acesso"
      >
        <button
          type="button"
          role="tab"
          :aria-selected="isRegister"
          :class="{ 'is-active': isRegister }"
          data-test="register-tab"
          @click="changeMode('register')"
        >
          Criar conta
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="!isRegister"
          :class="{ 'is-active': !isRegister }"
          data-test="login-tab"
          @click="changeMode('login')"
        >
          Entrar
        </button>
      </div>

      <div class="auth-form-heading">
        <p class="section-kicker">
          {{ isRegister ? 'Comece por você' : 'Bem-vindo de volta' }}
        </p>
        <h2>{{ isRegister ? 'Crie seu acesso' : 'Entre na sua conta' }}</h2>
        <p>
          {{
            isRegister
              ? 'Depois, você poderá cadastrar sua primeira organização.'
              : 'Use o e-mail e a senha cadastrados.'
          }}
        </p>
      </div>

      <form
        class="form-stack"
        data-test="auth-form"
        @submit.prevent="submit"
      >
        <template v-if="isRegister">
          <label class="field">
            <span>Nome completo</span>
            <input
              v-model.trim="fields.fullName"
              name="fullName"
              autocomplete="name"
              required
              placeholder="Ex.: Maria de Nazaré"
            >
          </label>

          <div class="field-row">
            <label class="field">
              <span>Como quer ser chamado</span>
              <input
                v-model.trim="fields.name"
                name="name"
                autocomplete="nickname"
                required
                placeholder="Ex.: Maria"
              >
            </label>
            <label class="field">
              <span>Nome preferido <small>opcional</small></span>
              <input
                v-model.trim="fields.preferredName"
                name="preferredName"
                placeholder="Ex.: Mari"
              >
            </label>
          </div>
        </template>

        <label class="field">
          <span>E-mail</span>
          <input
            v-model.trim="fields.email"
            name="email"
            type="email"
            autocomplete="email"
            required
            placeholder="voce@exemplo.com"
          >
        </label>

        <div :class="{ 'field-row': isRegister }">
          <label class="field">
            <span>Senha</span>
            <input
              v-model="fields.password"
              name="password"
              type="password"
              :autocomplete="isRegister ? 'new-password' : 'current-password'"
              minlength="8"
              required
              placeholder="Mínimo de 8 caracteres"
            >
          </label>
          <label
            v-if="isRegister"
            class="field"
          >
            <span>Confirme a senha</span>
            <input
              v-model="fields.passwordConfirmation"
              name="passwordConfirmation"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
              placeholder="Repita a senha"
            >
          </label>
        </div>

        <p
          v-if="errorMessage"
          class="form-error"
          data-test="auth-error"
          role="alert"
        >
          {{ errorMessage }}
        </p>

        <button
          class="primary-button"
          type="submit"
          :disabled="busy"
          data-test="auth-submit"
        >
          <span>{{ busy ? 'Aguarde…' : isRegister ? 'Criar minha conta' : 'Entrar no Eclesiapp' }}</span>
          <span aria-hidden="true">→</span>
        </button>
      </form>

      <p class="privacy-note">
        Seus dados pessoais ficam visíveis apenas para quem tem permissão na sua organização.
      </p>
    </div>
  </section>
</template>

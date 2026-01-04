import { isEmpty, isEmptyArray, isNullOrUndefined } from './helpers'

// 👉 Required Validator
export const requiredValidator = value => {
  if (isNullOrUndefined(value) || isEmptyArray(value) || value === false)
    return 'Trường này là bắt buộc' // Changed text

  return !!String(value).trim().length || 'Trường này là bắt buộc' // Changed text
}

// 👉 Email Validator
export const emailValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^(?:[^<>()[\]\\.,;:\s@"]+(?:\.[^<>()[\]\\.,;:\s@"]+)*|".+")@(?:\[\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\]|(?:[a-z\-\d]+\.)+[a-z]{2,})$/i
  if (Array.isArray(value))
    return value.every(val => re.test(String(val))) || 'Trường Email phải là một địa chỉ email hợp lệ' // Changed text

  return re.test(String(value)) || 'Trường Email phải là một địa chỉ email hợp lệ' // Changed text
}

// 👉 Password Validator
export const passwordValidator = password => {
  const regExp = /(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%&*()]).{8,}/
  const validPassword = regExp.test(password)

  return validPassword || 'Mật khẩu phải chứa ít nhất một chữ hoa, một chữ thường, một ký tự đặc biệt và một chữ số với tối thiểu 8 ký tự' // Changed text
}

// 👉 Confirm Password Validator
export const confirmedValidator = (value, target) => value === target || 'Xác nhận mật khẩu không khớp' // Changed text

// 👉 Between Validator
export const betweenValidator = (value, min, max) => {
  const valueAsNumber = Number(value)

  return (Number(min) <= valueAsNumber && Number(max) >= valueAsNumber) || `Vui lòng nhập số từ ${min} đến ${max}` // Changed text
}

// 👉 Integer Validator
export const integerValidator = value => {
  if (isEmpty(value))
    return true
  if (Array.isArray(value))
    return value.every(val => /^-?\d+$/.test(String(val))) || 'Trường này phải là một số nguyên' // Changed text

  return /^-?\d+$/.test(String(value)) || 'Trường này phải là một số nguyên' // Changed text
}

// 👉 Regex Validator
export const regexValidator = (value, regex) => {
  if (isEmpty(value))
    return true
  let regeX = regex
  if (typeof regeX === 'string')
    regeX = new RegExp(regeX)
  if (Array.isArray(value))
    return value.every(val => regexValidator(val, regeX))

  return regeX.test(String(value)) || 'Định dạng của trường này không hợp lệ' // Changed text
}

// 👉 Alpha Validator
export const alphaValidator = value => {
  if (isEmpty(value))
    return true

  return /^[A-Z]*$/i.test(String(value)) || 'Trường này chỉ được chứa các ký tự chữ cái' // Changed text
}

// 👉 URL Validator
export const urlValidator = value => {
  if (isEmpty(value))
    return true
  const re = /^https?:\/\/[^\s$.?#].\S*$/

  return re.test(String(value)) || 'URL không hợp lệ' // Changed text
}

// 👉 Length Validator
export const lengthValidator = (value, length) => {
  if (isEmpty(value))
    return true

  return String(value).length === length || `Độ dài của trường ký tự phải là ${length} ký tự.` // Changed text
}

// 👉 Alpha-dash Validator
export const alphaDashValidator = value => {
  if (isEmpty(value))
    return true
  const valueAsString = String(value)

  return /^[\w-]*$/.test(valueAsString) || 'Tất cả các ký tự không hợp lệ' // Changed text
}

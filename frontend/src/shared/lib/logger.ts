export type DetailedData = Record<string, unknown>

export const appLogger = {
  log(methodName: string, detailedData: DetailedData = {}) {
    console.debug(`log ( { ${methodName} }, detailed_data )`, {
      'method-name': methodName,
      detailed_data: detailedData,
    })
  },
}
